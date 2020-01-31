<?php

namespace OndraKoupil\AppTools\Data;

class StructureItem {

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $master;

	/**
	 * @var StructureItem[]
	 */
	public $children = array();

	/**
	 * @var int[]
	 */
	public $level = 0;

	/**
	 * @var int[]
	 */
	public $path = array();

	function __construct($id, $master) {
		$this->id = $id;
		$this->master = $master;
	}

}

