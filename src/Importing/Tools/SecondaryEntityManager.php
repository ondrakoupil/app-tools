<?php

namespace OndraKoupil\AppTools\Importing\Tools;

use NotORM;
use NotORM_Result;

/**
 * Pomůcka pro sledování vedlejších entit, např. kategorií nebo tagů
 */
class SecondaryEntityManager {

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
	protected $nameColumn;

	/**
	 * @var string
	 */
	protected $idColumn;

	protected $nameProcessCallback;

	protected $allItemsCallback;


	protected $dataCacheByName = null;
	protected $dataCacheById = null;

	protected $insertNewItemCallback = null;


	/**
	 * @param DatabaseWrapper $db
	 * @param string $tableName
	 * @param string $nameColumn Který sloupec se má považovat za jméno?
	 * @param string $idColumn
	 */
	function __construct(
		DatabaseWrapper $db,
		$tableName,
		$nameColumn,
		$idColumn = 'id'
	) {
		$this->db = $db;
		$this->tableName = $tableName;
		$this->nameColumn = $nameColumn;
		$this->idColumn = $idColumn;
	}

	/**
	 * @param callable $insertNewItemCallback function($valuesToInsert) => $modifiedValuesToInsert
	 */
	public function setInsertNewItemCallback(callable $insertNewItemCallback) {
		$this->insertNewItemCallback = $insertNewItemCallback;
	}


	/**
	 * @return NotORM_Result
	 */
	protected function getTable() {
		$tableName = $this->tableName;
		return $this->db->getDb()->$tableName();
	}

	/**
	 * @return NotORM_Result
	 */
	protected function getInsertTable() {
		$tableName = $this->tableName;
		return $this->db->getWriteDb()->$tableName();
	}


	/**
	 * @return NotORM_Result
	 */
	protected function getAllItems() {
		$request = $this->getTable()->select($this->idColumn . ', ' . $this->nameColumn);
		if ($this->allItemsCallback) {
			$requestProcessed = call_user_func_array($this->allItemsCallback, array($request));
			if ($requestProcessed) {
				return $requestProcessed;
			}
		}
		return $request;
	}

	/**
	 * Funkce pro úpravu/normalizaci názvu
	 *
	 * @param callable $nameProcessCallback function($name) => $procesedName
	 */
	public function setNameProcessCallback(callable $nameProcessCallback) {
		$this->nameProcessCallback = $nameProcessCallback;
	}

	/**
	 * Funkce pro úpravu dat pro ulžoení do databáze, když se má vytvořit nová položka
	 *
	 * @param callable $allItemsCallback (NotORM_Result $request) => NotORM_Result
	 */
	public function setAllItemsCallback(callable $allItemsCallback): void {
		$this->allItemsCallback = $allItemsCallback;
	}


	/**
	 * Pokud již položka s jménem existuje, vrátí ji, pokud ne, vytvoří ji.
	 *
	 * @param string $name
	 * @param array $moreDataToCreate Dodatečná data na vytvoření nové položky.
	 *
	 * @return array
	 */
	public function getOrCreateByName($name, $moreDataToCreate = array()) {
		$this->loadIfNeeded();
		$existing = $this->getByName($name);
		if ($existing) {
			return $existing;
		} else {
			$data = array($this->nameColumn => $name) + $moreDataToCreate;
			if ($this->insertNewItemCallback) {
				$data = call_user_func_array($this->insertNewItemCallback, array($data));
			}
			$inserted = $this->getInsertTable()->insert($data);
			if ($inserted) {
				$givenId = $inserted[$this->idColumn];
			} else {
				$givenId = $this->db->generateFakeId();
				$inserted = $data;
				$inserted[$this->idColumn] = $givenId;
			}
			$processedName = $this->processName($name);
			$this->dataCacheByName[$processedName] = $inserted;
			$this->dataCacheById[$givenId] = $inserted;
			return $inserted;
		}
	}


	/**
	 * Vrací existující položku nebo null
	 *
	 * @param int $id
	 *
	 * @return array|null
	 */
	public function getById($id) {
		$this->loadIfNeeded();
		return (isset($this->dataCacheById[$id]) ? $this->dataCacheById[$id] : null);
	}

	/**
	 * Vrací existující položku nebo null
	 *
	 * @param string $name
	 *
	 * @return array|null
	 */
	public function getByName($name) {
		$this->loadIfNeeded();
		$name = $this->processName($name);
		return (isset($this->dataCacheByName[$name]) ? $this->dataCacheByName[$name] : null);
	}

	/**
	 * Existuje položka?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function existsName($name) {
		$this->loadIfNeeded();
		$name = $this->processName($name);
		return isset($this->dataCacheByName[$name]);
	}

	/**
	 * Existuje ID?
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function existsId($id) {
		$this->loadIfNeeded();
		return isset($this->dataCacheById[$id]);
	}


	protected function loadIfNeeded() {
		if ($this->dataCacheByName === null) {
			$this->dataCacheById = array();
			$this->dataCacheByName = array();

			foreach ($this->getAllItems() as $row) {
				$name = $this->processName($row[$this->nameColumn]);
				$id = $row[$this->idColumn];

				$this->dataCacheById[$id] = $row;
				$this->dataCacheByName[$name] = $row;
			}

		}
	}

	protected function processName($name) {
		if ($this->nameProcessCallback) {
			$processedName = call_user_func_array($this->nameProcessCallback, array($name));
			if ($processedName) {
				return $processedName;
			}
		}
		return $name;
	}

}
