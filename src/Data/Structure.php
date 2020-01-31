<?php

namespace OndraKoupil\AppTools\Data;

use PDO;

class Structure {

	/**
	 * @var StructureItem[]
	 */
	protected $data = null;

	protected $rootItems = array();

	/**
	 * @var callable
	 */
	protected $dataGetter = null;

	function __construct($dataGetter) {
		$this->dataGetter = $dataGetter;
	}

	/**
	 * @param int $id
	 *
	 * @return StructureItem|null
	 */
	public function getItem($id) {
		$this->loadIfNeeded();
		return $this->data[$id] ?? null;
	}

	/**
	 * @return StructureItem[]
	 */
	public function getRootItems() {
		$this->loadIfNeeded();
		return $this->rootItems;
	}

	/**
	 * @return void
	 */
	public function reload() {
		$this->data = null;
		$this->loadIfNeeded();
	}

	protected function loadIfNeeded() {
		if ($this->data === null) {
			$this->data = array();

			$rows = call_user_func_array($this->dataGetter, array());

			$childrenIds = array();

			foreach ($rows as $row) {
				$id = +$row['id'];
				$master = $row['master'] ? +$row['master'] : 0;

				$this->data[$id] = new StructureItem($id, $master);

				if (!isset($childrenIds[$master])) {
					$childrenIds[$master] = array();
				}
				$childrenIds[$master][] = $id;
			}

			foreach ($childrenIds as $masterId => $children) {
				$this->data[$masterId]->children = array_map(function($id) {
					return $this->data[$id];
				}, $children);
			}

			foreach ($this->data as $id => $row) {
				$path = array();
				$pos = $id;
				$safe = 0;
				while ($pos > 0 and $safe < 20) {
					$safe++;
					$pos = $this->data[$pos]->master;
					if ($pos) {
						$path[] = $pos;
					}
				}
				$path = array_reverse($path);
				$path[] = $id;
				$this->data[$id]->path = $path;
				$this->data[$id]->level = count($path);
			}

			$this->rootItems = array_values(array_filter($this->data, function($item) {
				return !$item->master;
			}));

		}
	}

}
