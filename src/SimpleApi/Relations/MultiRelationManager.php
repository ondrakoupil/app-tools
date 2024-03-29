<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

use NotORM;
use OndraKoupil\Tools\Arrays;

class MultiRelationManager {

	protected $cachedData = array();

	protected $cachedDataAll = false;


	/**
	 * @var NotORM
	 */
	private $db;

	/**
	 * @var string
	 */
	private $relationTableName;

	/**
	 * @var string
	 */
	private $myColumnName;

	/**
	 * @var string
	 */
	private $otherColumnName;

	protected bool $useStringIds;

	function __construct(
		NotORM $db,
		string $relationTableName,
		string $myColumnName,
		string $otherColumnName,
		bool $useStringIds = true
	) {

		$this->db = $db;
		$this->relationTableName = $relationTableName;
		$this->myColumnName = $myColumnName;
		$this->otherColumnName = $otherColumnName;
		$this->useStringIds = $useStringIds;
	}

	/**
	 * @param bool $useStringIds
	 */
	public function setUseStringIds(bool $useStringIds): void {
		$this->useStringIds = $useStringIds;
	}


	/**
	 * @param string $id
	 *
	 * @return string[]
	 */
	function get(string $id): array {
		$this->preload($id);
		return $this->cachedData[$id] ?? array();
	}


	/**
	 * @param string[] $ids
	 *
	 * @return string[] [my-ID] => relatedIds[]
	 */
	function getMany(array $ids): array {
		$this->preloadMany($ids);
		$returned = array();
		foreach ($ids as $id) {
			$returned[$id] = $this->cachedData[$id] ?? array();
		}
		return $returned;
	}

	/**
	 * @return array [idParent] => idChildren[]
	 */
	function getAll(): array {
		$this->preloadAll();
		return $this->cachedData;
	}

	function set(string $id, array $relatedIds) {
		$this->clear($id);
		$this->add($id, $relatedIds);
	}

	function add(string $id, array $relatedIds) {
		$valuesToInsert = array();
		$this->preload($id);
		$current = $this->get($id);
		foreach ($relatedIds as $relatedId) {
			if (!in_array($relatedId, $current)) {
				$valuesToInsert[] = array($this->myColumnName => $id, $this->otherColumnName => $relatedId);
			}
		}
		$tableName = $this->relationTableName;
		$this->db->$tableName()->insert_multi($valuesToInsert);
		if (isset($this->cachedData[$id])) {
			foreach ($relatedIds as $relatedId) {
				if (!in_array($relatedId, $this->cachedData[$id])) {
					$this->cachedData[$id][] = $relatedId;
				}
			}
		}
	}

	function delete(string $id, array $relatedIds) {
		$tableName = $this->relationTableName;
		$this->db->$tableName()->where($this->myColumnName, $id)->where($this->otherColumnName, $relatedIds)->delete();
		if (isset($this->cachedData[$id])) {
			$this->cachedData[$id] = Arrays::deleteValues($this->cachedData[$id], $relatedIds);
		}
	}

	function clear(string $id) {
		$tableName = $this->relationTableName;
		$this->db->$tableName()->where($this->myColumnName, $id)->delete();
		$this->cachedData[$id] = array();
	}


	function preloadAll(): void {
		if ($this->cachedDataAll) {
			return;
		}

		$loadedRows = $this->loadSomeData();
		$this->saveLoadedData($loadedRows);
		$this->cachedDataAll = true;
	}

	function preload(string $id): void {
		if ($this->cachedDataAll or ($this->cachedData[$id] ?? null) !== null) {
			return;
		}
		$loadedRows = $this->loadSomeData($this->myColumnName, $id);
		$this->saveLoadedData($loadedRows, array($id));
	}

	/**
	 * @param string[] $ids
	 */
	function preloadMany(array $ids): void {
		if ($this->cachedDataAll) {
			return;
		}
		$idsToLoad = array();
		foreach ($ids as $id) {
			if (!isset($this->cachedData[$id]) or $this->cachedData[$id] === null) {
				$idsToLoad[] = $id;
			}
		}
		if ($idsToLoad) {
			$loadedRows = $this->loadSomeData($this->myColumnName, $idsToLoad);
			$this->saveLoadedData($loadedRows, $idsToLoad);
		}
	}


	/**
	 * @param string|null $whereField
	 * @param string|string[] $whereValue
	 *
	 * @return array Array dvojic ['my', 'other']
	 */
	protected function loadSomeData(string $whereField = null, $whereValue = null): array {
		$tableName = $this->relationTableName;
		$request = $this->db->$tableName();
		if ($whereField) {
			$request->where($whereField, $whereValue);
		}
		$request->select($this->myColumnName, $this->otherColumnName);
		$result = array();
		foreach ($request as $row) {
			$myId = $row[$this->myColumnName];
			$otherId = $row[$this->otherColumnName];
			if ($this->useStringIds) {
				$myId .= '';
				$otherId .= '';
			}
			$result[] = array(
				'my' => $myId,
				'other' => $otherId,
			);
		}
		return $result;
	}

	protected function saveLoadedData($loadedRows, $knownMyIds = array()) {
		if ($knownMyIds) {
			foreach ($knownMyIds as $id) {
				if (!isset($this->cachedData[$id])) {
					$this->cachedData[$id] = array();
				}
			}
		}
		foreach ($loadedRows as $row) {
			if (!isset($this->cachedData[$row['my']])) {
				$this->cachedData[$row['my']] = array();
			}
			$this->cachedData[$row['my']][] = $row['other'];
		}
	}


}
