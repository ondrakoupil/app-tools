<?php

namespace OndraKoupil\AppTools\Importing\Tools;

use NotORM;

/**
 * Zefektivňuje dávkové vkládání do DB.
 *
 * ```php
 * $inserter = new MassDbInserter($db, 'some_table');
 *
 * foreach ($someData as $item) {
 *    $inserter->insert($item);
 * }
 *
 * $inserter->save();
 *
 * ```
 */
class MassDbInserter {

	/**
	 * @var DatabaseWrapper
	 */
	protected $db;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var int
	 */
	protected $batchSize = 25;

	/**
	 * @var array
	 */
	protected $buffer = array();

	/**
	 * @param DatabaseWrapper $db
	 * @param string $tableName
	 */
	public function __construct(DatabaseWrapper $db, $tableName, $batchSize = 0) {
		$this->db = $db;
		$this->tableName = $tableName;
		if ($batchSize) {
			$this->setBatchSize($batchSize);
		}
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize($batchSize): void {
		$this->batchSize = $batchSize;
	}

	/**
	 * Vloží další položku do bufferu
	 * a pokud je už dost velký, uloží je do DB.
	 *
	 * @param array $item
	 *
	 * @return void
	 */
	public function insert($item) {
		$this->buffer[] = $item;
		$this->save(false);
	}

	/**
	 * Uloží zbývající položky v bufferu.
	 *
	 * @param bool $force
	 *
	 * @return void
	 */
	public function save($force = true) {
		if (!$force and count($this->buffer) < $this->batchSize) {
			return;
		}

		$this->db->getWriteDb()->{$this->tableName}()->insert_multi($this->buffer);
		$this->buffer = array();
	}




}
