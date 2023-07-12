<?php

namespace OndraKoupil\AppTools\Importing\Importer;

use Exception;
use NotORM;
use NotORM_Result;
use NotORM_Row;
use OndraKoupil\AppTools\Importing\Reader\ReaderInterface;
use OndraKoupil\AppTools\Importing\Tools\DatabaseWrapper;
use OndraKoupil\AppTools\Importing\Tools\MassDbInserter;
use OndraKoupil\Tools\Strings;
use RuntimeException;

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
	protected $keepOtherFieldsAfterApplyingMappings = false;
	protected $applyMappingsBeforeTransformCallback = false;

	protected $truncateBefore = false;

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
	public function setMappings(array $mappings, $keepOtherFieldsAfterApplyingMappings = false, $applyBeforeTransformCallback = false): void {
		$this->mappings = $mappings;
		$this->keepOtherFieldsAfterApplyingMappings = $keepOtherFieldsAfterApplyingMappings;
		$this->applyMappingsBeforeTransformCallback = $applyBeforeTransformCallback;
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
			$this->processOneItem($item, $this->reader->getCurrentPosition());
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
				$this->massInserter->insert($item);
				$givenId = null;
			} else {
				$inserted = $this->getInsertTable()->insert($item);
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

	protected static function processMappings(mixed $savableItem, mixed $mappings, $keepOtherFields = false) {
		if (!$keepOtherFields) {
			$out = array();
		} else {
			$out = $savableItem;
		}

		foreach ($mappings as $origField => $newField) {
			$out[$newField] = $savableItem[$origField] ?? null;
			if ($keepOtherFields) {
				unset($out[$origField]);
			}
		}
		return $out;
	}

}
