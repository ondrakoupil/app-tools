<?php

namespace OndraKoupil\AppTools\Importing\Importer;

use NotORM;
use NotORM_Result;
use NotORM_Row;
use OndraKoupil\AppTools\Importing\Reader\ReaderInterface;
use OndraKoupil\AppTools\Importing\Tools\MassDbInserter;
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
	 * @var NotORM
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

	/**
	 * @param NotORM $db
	 * @param string $tableName
	 * @param ReaderInterface $reader
	 * @param callable $transformCallback function($readRow, $itemPosition) => $dataToSaveToDb
	 */
	public function __construct(NotORM $db, string $tableName, ReaderInterface $reader, callable $transformCallback = null) {
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
	 * Spustí import.
	 *
	 * @return void
	 */
	function import() {
		if (!$this->reader) {
			throw new RuntimeException('No reader has been specified.');
		}
		$this->importedItemsCount = 0;

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
		return $this->db->{$this->tableName}();
	}


	protected function processOneItem($item, $itemPosition) {
		$savableItem = call_user_func_array($this->transformCallback, array($item, $itemPosition));
		if (!$savableItem) {
			return;
		}

		$givenId = $this->insertToDb($item);

		if ($this->afterSaveCallback) {
			call_user_func_array($this->afterSaveCallback, array($savableItem, $givenId, $item, $itemPosition));
		}

		$this->importedItemsCount++;

	}

	protected function insertToDb($item) {
		if ($this->massInserter) {
			$this->massInserter->insert($item);
			$givenId = null;
		} else {
			$inserted = $this->getTable()->insert($item);
			$givenId = $inserted[$this->idColumn];
		}
		return $givenId;
	}

	public function getImportedItemsCount() {
		return $this->importedItemsCount;
	}

}
