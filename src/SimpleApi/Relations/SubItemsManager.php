<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

use NotORM;

class SubItemsManager {

	protected $cachedParentData = array();

	protected $cachedParentDataAll = false;

	protected $cachedChildData = array();

	protected $cachedChildDataAll = false;

	/**
	 * @var NotORM
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $childTableName;

	/**
	 * @var string
	 */
	protected $childForeignKeyName;

	/**
	 * @var string
	 */
	protected $childIdName;

	function __construct(
		NotORM $db,
		string $childTableName,
		string $childForeignKeyName,
		string $childIdName = 'id'
	) {

		$this->db = $db;
		$this->childTableName = $childTableName;
		$this->childForeignKeyName = $childForeignKeyName;
		$this->childIdName = $childIdName;
	}

	/**
	 * Use to speed up using getChildrenOf* methods
	 */
	function preloadAllParents(): void {
		if ($this->cachedParentDataAll) {
			return;
		}

		$loadedRows = $this->loadSomeData();
		$this->saveLoadedParentData($loadedRows);
		$this->cachedParentDataAll = true;
	}

	/**
	 * Use to speed up using getChildrenOf* methods
	 *
	 * @param string $id
	 */
	function preloadParent(string $id): void {
		if ($this->cachedParentDataAll or ($this->cachedParentData[$id] ?? null) !== null) {
			return;
		}
		$loadedRows = $this->loadSomeData($this->childForeignKeyName, $id);
		$this->saveLoadedParentData($loadedRows, array($id));
	}

	/**
	 * Use to speed up using getChildrenOf* methods
	 *
	 * @param string[] $ids
	 */
	function preloadManyParents(array $ids): void {
		if ($this->cachedParentDataAll) {
			return;
		}
		$idsToLoad = array();
		foreach ($ids as $id) {
			if (!isset($this->cachedParentData[$id]) or $this->cachedParentData[$id] === null) {
				$idsToLoad[] = $id;
			}
		}
		if ($idsToLoad) {
			$loadedRows = $this->loadSomeData($this->childForeignKeyName, $idsToLoad);
			$this->saveLoadedParentData($loadedRows, $idsToLoad);
		}
	}

	/**
	 * Use to speed up using getParentOf* methods
	 */
	function preloadAllChildren(): void {
		if ($this->cachedChildDataAll) {
			return;
		}
		$loadedRows = $this->loadSomeData();
		$this->saveLoadedChildrenData($loadedRows);
		$this->cachedChildDataAll = true;
	}

	/**
	 * Use to speed up using getParentOf* methods
	 *
	 * @param string $id
	 */
	function preloadChild(string $id): void {
		if ($this->cachedChildDataAll or array_key_exists($id, $this->cachedChildData)) {
			return;
		}
		$loadedRows = $this->loadSomeData($this->childIdName, $id);
		$this->saveLoadedChildrenData($loadedRows, array($id));

	}

	/**
	 * Use to speed up using getParentOf* methods
	 *
	 * @param string[] $ids
	 */
	function preloadManyChildren(array $ids): void {
		if ($this->cachedChildDataAll) {
			return;
		}
		$idsToLoad = array();
		foreach ($ids as $id) {
			if (!array_key_exists($id, $this->cachedChildData)) {
				$idsToLoad[] = $id;
			}
		}
		if ($idsToLoad) {
			$loadedRows = $this->loadSomeData($this->childIdName, $idsToLoad);
			$this->saveLoadedChildrenData($loadedRows, $idsToLoad);
		}
	}

	/**
	 * @param string $id
	 *
	 * @return string[]
	 */
	function getChildrenOf(string $id): array {
		$this->preloadParent($id);
		return $this->cachedParentData[$id] ?? array();
	}

	/**
	 * @param string[] $ids
	 *
	 * @return array [idParent] => idChildren[]
	 */
	function getChildrenOfMany(array $ids): array {
		$this->preloadManyParents($ids);
		$returned = array();
		foreach ($ids as $id) {
			$returned[$id] = $this->cachedParentData[$id] ?? array();
		}
		return $returned;
	}

	/**
	 * @return array [idParent] => idChildren[]
	 */
	function getChildrenOfAll(): array {
		$this->preloadAllParents();
		return $this->cachedParentData;
	}


	function getParentOf(string $id): ?string {
		$this->preloadChild($id);
		return $this->cachedChildData[$id] ?? null;
	}

	/**
	 * @param string[] $ids
	 *
	 * @return array [idChild] => idParent nebo null
	 */
	function getParentOfMany(array $ids): array {
		$this->preloadManyChildren($ids);
		$returned = array();
		foreach ($ids as $id) {
			$returned[$id] = $this->cachedChildData[$id] ?? null;
		}
		return $returned;
	}

	/**
	 * @return array [idChild] => idParent nebo null
	 */
	function getParentOfAll(): array {
		$this->preloadAllChildren();
		return $this->cachedChildData;
	}


	/**
	 * @param string $whereField
	 * @param string|string[] $whereValue
	 *
	 * @return array Array dvojic ['parent', 'child']
	 */
	protected function loadSomeData($whereField = null, $whereValue = null): array {
		$tableName = $this->childTableName;
		$request = $this->db->$tableName();
		if ($whereField) {
			$request->where($whereField, $whereValue);
		}
		$request->select($this->childForeignKeyName, $this->childIdName);
		$result = array();
		foreach ($request as $row) {
			$result[] = array(
				'parent' => $row[$this->childForeignKeyName],
				'child' => $row[$this->childIdName],
			);
		}
		return $result;
	}

	protected function saveLoadedParentData($loadedRows, $knownParentIds = array()) {
		if ($knownParentIds) {
			foreach ($knownParentIds as $id) {
				if (!isset($this->cachedParentData[$id])) {
					$this->cachedParentData[$id] = array();
				}
			}
		}
		foreach ($loadedRows as $row) {
			if (!isset($this->cachedParentData[$row['parent']])) {
				$this->cachedParentData[$row['parent']] = array();
			}
			$this->cachedParentData[$row['parent']][] = $row['child'];
		}
	}

	protected function saveLoadedChildrenData($loadedRows, $knownChildIds = array()) {
		if ($knownChildIds) {
			foreach ($knownChildIds as $id) {
				if (!isset($this->cachedChildData[$id])) {
					$this->cachedChildData[$id] = null;
				}
			}
		}
		foreach ($loadedRows as $row) {
			$this->cachedChildData[$row['child']] = $row['parent'];
		}
	}

}
