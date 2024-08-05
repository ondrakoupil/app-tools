<?php

namespace OndraKoupil\AppTools\Importing\Importer;

use ArrayAccess;
use Exception;
use NotORM;
use NotORM_Literal;
use NotORM_Result;
use NotORM_Row;
use OndraKoupil\AppTools\Importing\Reader\ReaderInterface;
use OndraKoupil\AppTools\Importing\Tools\DatabaseWrapper;
use OndraKoupil\AppTools\Importing\Tools\MassDbInserter;
use OndraKoupil\Tools\Strings;
use RuntimeException;

/**
 * Importér, který načtená data ukládá do databáze.
 *
 * Používá DatabaseWrapper. Zavolej $db->enableDryRun(), pokud chceš import jen vyzkoušet.
 *
 * Zajímavé parametry a featury:
 *
 * - setBatchSize - zapojí MassDbInserter a ukládá více položek naráz
 * - setMappings - automatické mapování políček ze zdroje do cíle, buď úplný výčet, nebo jen ta která neodpovídají
 * - setFixedValues - automaticky přidá nějaké napevno dané hodnoty
 * - setDuplicateColumns - zapne používání "on duplicate update" query, definuje sloupce, které se v případě kolize ID mají aktualizovat
 * - setTransformCallback - manuální callback na transformaci
 * - setTruncateBefore - umožní promazat cílovou tabulku před importem
 * - setSaveHandler - umožní místo výchozího dumpování přímo do databáze zavolat jiný callback
 *
 */
class DatabaseImporter implements ImporterInterface {

	/**
	 * @var ReaderInterface
	 */
	protected $reader;

	/**
	 * @var callable
	 */
	protected $transformCallback;

	/**
	 * @var callable
	 */
	protected $afterSaveCallback;

	/**
	 * @var DatabaseWrapper
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var string
	 */
	protected $idColumn = 'id';

	/**
	 * @var MassDbInserter
	 */
	protected $massInserter;

	protected $importedItemsCount = 0;

	protected $mappings = null;
	protected $keepOtherFieldsAfterApplyingMappings = true;
	protected $applyMappingsBeforeTransformCallback = true;

	protected $fixedValues = null;

	protected $truncateBefore = false;

	protected $errorHandler;

	protected $saveHandler;

	protected $duplicateColumns = array();

	/**
	 * @param DatabaseWrapper $db
	 * @param string $tableName
	 * @param ReaderInterface $reader
	 * @param ?callable $transformCallback function($readRow, $itemPosition) => $dataToSaveToDb
	 */
	public function __construct(DatabaseWrapper $db, string $tableName, ReaderInterface $reader, ?callable $transformCallback = null) {
		$this->db = $db;
		$this->tableName = $tableName;
		$this->transformCallback = $transformCallback;
		$this->reader = $reader;
	}

	/**
	 * @param callable $afterSaveCallback function ($item, $id, $row, $itemPosition) => void;
	 *
	 * $item jsou data určená pro databázi
	 * $id je ID přidělené databází
	 * $row jsou syrová data před zpracováním z $transformCallback
	 * $itemPosition je pozice z readeru (nejspíš číslo řádku)
	 */
	public function setAfterSaveCallback(callable $afterSaveCallback): void {
		$this->afterSaveCallback = $afterSaveCallback;
	}

	/**
	 * @param bool $truncateBefore
	 */
	public function setTruncateBefore(bool $truncateBefore): void {
		$this->truncateBefore = $truncateBefore;
	}

	/**
	 * Nastaví, jaké ID v databázi se má považovat za ID
	 * @param string $idColumn
	 */
	public function setIdColumn(string $idColumn): void {
		$this->idColumn = $idColumn;
	}

	/**
	 * @param ReaderInterface $reader
	 *
	 * @return void
	 */
	function setReader(ReaderInterface $reader) {
		$this->reader = $reader;
	}

	/**
	 * Debugovací NotORM spojení
	 *
	 * @param NotORM $notORM
	 *
	 * @return void
	 */
	function setWriteDb(NotORM $notORM) {
		$this->writeDb = $notORM;
	}

	/**
	 * @param callable $saveHandler (array $item, NotORM_Result $insertTable, DatabaseWrapper $db): return string ID of inserted item
	 *
	 * @return void
	 */
	function setSaveHandler(callable $saveHandler) {
		$this->saveHandler = $saveHandler;
	}

	/**
	 *
	 * @param string[] $duplicateColumns
	 *
	 * @return void
	 */
	public function setDuplicateColumns(array $duplicateColumns): void {
		$this->duplicateColumns = $duplicateColumns;
	}

	/**
	 * Nastaví funkci, která se spustí, pokud během procesingu nebo parsingu nastane chyba (vyhozena výjimka).
	 * Pokud je nastaven, výjimka nezastaví import, ale je předána handleru. Pokud ale i handler vyhodí výjimku,
	 * je import přerušen.
	 *
	 * Funkce dostane výjimku a číslo řádku.
	 */
	function setErrorHandler(callable $handler) {
		$this->errorHandler = $handler;
	}

	/**
	 * Transformuje načtenou položku z Readeru do podoby dat pro databázi.
	 *
	 * @param callable $callback function($readRow, $itemPosition) => $dataToSaveToDb
	 *
	 * @return void
	 */
	function setTransformCallback(callable $callback) {
		$this->transformCallback = $callback;
	}

	/**
	 * Nastaví mapování původních sloupečků na nové.
	 *
	 * @param array $mappings
	 */
	public function setMappings(array $mappings, $keepOtherFieldsAfterApplyingMappings = true, $applyBeforeTransformCallback = true): void {
		$this->mappings = $mappings;
		$this->keepOtherFieldsAfterApplyingMappings = $keepOtherFieldsAfterApplyingMappings;
		$this->applyMappingsBeforeTransformCallback = $applyBeforeTransformCallback;
	}

	/**
	 * @param array $fixedValues
	 */
	public function setFixedValues(array $fixedValues): void {
		$this->fixedValues = $fixedValues;
	}



	/**
	 * Spustí import.
	 *
	 * @return void
	 */
	function import() {
		if (!$this->reader) {
			throw new RuntimeException('No reader has been specified.');
		}
		$this->importedItemsCount = 0;

		if ($this->truncateBefore) {
			$this->getInsertTable()->delete();
		}

		$this->reader->startReading();

		while ($item = $this->reader->readNextItem()) {
			try {
				$this->processOneItem($item, $this->reader->getCurrentPosition());
			} catch (Exception $e) {
				if ($this->errorHandler) {
					call_user_func_array($this->errorHandler, array($e, $this->reader->getCurrentPosition()));
				} else {
					throw $e;
				}
			}
		}

		$this->reader->endReading();
		if ($this->massInserter) {
			$this->massInserter->save();
		}

	}

	/**
	 * @return NotORM_Result
	 */
	protected function getTable() {
		return $this->db->getDb()->{$this->tableName}();
	}

	/**
	 * @return NotORM_Result
	 */
	protected function getInsertTable() {
		return $this->db->getWriteDb()->{$this->tableName}();
	}

	public function setBatchSize(int $batchSize): void {
		if ($batchSize <= 1) {
			$this->massInserter = null;
		} else {
			$this->massInserter = new MassDbInserter(
				$this->db,
				$this->tableName,
				$batchSize
			);
		}
	}



	protected function processOneItem($item, $itemPosition) {

		if ($this->mappings and $this->applyMappingsBeforeTransformCallback) {
			$item = self::processMappings($item, $this->mappings, $this->keepOtherFieldsAfterApplyingMappings);
		}

		if ($this->fixedValues) {
			$item = self::processFixedValues($item, $this->fixedValues);
		}

		if ($this->transformCallback) {
			$savableItem = call_user_func_array($this->transformCallback, array($item, $itemPosition));
			if (!$savableItem) {
				return;
			}
		} else {
			$savableItem = $item;
		}

		if ($this->mappings and !$this->applyMappingsBeforeTransformCallback) {
			$savableItem = self::processMappings($savableItem, $this->mappings, $this->keepOtherFieldsAfterApplyingMappings);
		}

		$givenId = $this->insertToDb($savableItem);

		if ($this->afterSaveCallback) {
			call_user_func_array($this->afterSaveCallback, array($savableItem, $givenId, $item, $itemPosition));
		}

		$this->importedItemsCount++;

	}

	protected function insertToDb($item) {

		try {
			if ($this->massInserter) {
				if ($this->saveHandler or $this->duplicateColumns) {
					throw new Exception('You can\'t use MassInserter and custom save handler or duplicate columns at the same time.');
				}
				$this->massInserter->insert($item);
				$givenId = null;
			} else {

				if ($this->saveHandler) {
					$inserted = call_user_func_array($this->saveHandler, array(
						$item,
						$this->getInsertTable(),
						$this->db
					));
				} else {

					if ($this->duplicateColumns and $item[$this->idColumn]) {

						$unique = array($this->idColumn => $item[$this->idColumn]);
						$dataToInsert = $item;
						unset($dataToInsert[$this->idColumn]);
						$dataToUpdate = array();
						foreach ($this->duplicateColumns as $col) {
							$dataToUpdate[$col] = $dataToInsert[$col];
						}

						$inserted = $this->getInsertTable()->insert_update($unique, $dataToInsert, $dataToUpdate);
						if (!is_array($inserted) and !($inserted instanceof ArrayAccess)) {
							// PG
							$inserted = $item;
						}


					} else {
						$inserted = $this->getInsertTable()->insert($item);
					}


				}

				if (!$inserted) {
					$givenId = $this->db->generateFakeId();
				} else {
					$givenId = $inserted[$this->idColumn];
				}
			}
		} catch (Exception $e) {
			$itemId = $item['id'] ?? print_r($item, true);
			throw new Exception('Error when saving to database - item ' . $itemId, 1, $e);
		}


		return $givenId;
	}

	public function getImportedItemsCount() {
		return $this->importedItemsCount;
	}

	/**
	 *
	 *
	 * @param array $item
	 * @param array|null $mappings
	 * @param bool $keepOtherFields
	 *
	 * @return array
	 */
	protected static function processMappings(array $item, array $mappings, bool $keepOtherFields = false): array {
		if (!$keepOtherFields) {
			$out = array();
		} else {
			$out = $item;
		}

		if ($mappings) {
			foreach ($mappings as $origField => $newField) {
				$out[$newField] = $item[$origField] ?? null;
				if ($keepOtherFields) {
					unset($out[$origField]);
				}
			}
		}

		return $out;
	}

	protected static function processFixedValues(array $item, ?array $fixedValues): array {
		$out = $item;
		if ($fixedValues) {
			foreach ($fixedValues as $fixedValueName => $fixedValueValue) {
				$out[$fixedValueName] = $fixedValueValue;
			}
		}
		return $out;
	}

}
