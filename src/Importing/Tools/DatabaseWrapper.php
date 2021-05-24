<?php

namespace OndraKoupil\AppTools\Importing\Tools;

use NotORM;
use PDO;

/**
 * Zabaluje PDO a NotORM a umožňuje vytvořit "falešný" debugovací výstup.
 */
class DatabaseWrapper {

	/**
	 * @var PDO
	 */
	private $pdo;

	/**
	 * @var NotORM
	 */
	private $notORM;

	/**
	 * @var NotORM
	 */
	private $debugDb;

	protected $lastFakeID = 0;

	function __construct(PDO $db) {
		$this->pdo = $db;
	}

	/**
	 * Nstaví na dry-run pomocí fakeového NotORM
	 *
	 * @param null|callable $queryCallback function ($query, $parameters) => void
	 * Je-li null, použije se self::echoQuery
	 *
	 * @return void
	 */
	function enableDryRun($queryCallback = null) {
		$this->createNotORMIfNeeded();
		$this->notORM->debug = null;
		$this->debugDb = new NotORM($this->pdo);
		if ($queryCallback) {
			$this->debugDb->debug = function($query, $parameters) use ($queryCallback){
				call_user_func_array($queryCallback, array($query, $parameters));
				return false;
			};
		} else {
			$this->debugDb->debug = function($query, $parameters) {
				self::echoQuery($query, $parameters);
				return false;
			};
		}
	}

	/**
	 * @param null|callable $queryCallback function ($query, $parameters) => void
	 * Je-li null, použije se self::echoQuery
	 *
	 * @return void
	 */
	function enableTrackingQueries($queryCallback = null) {
		$this->createNotORMIfNeeded();
		$this->debugDb = null;
		if ($queryCallback) {
			$this->notORM->debug = $queryCallback;
		} else {
			$this->notORM->debug = function($query, $parameters) {
				self::echoQuery($query, $parameters);
			};
		}
	}

	protected function createNotORMIfNeeded() {
		if (!$this->notORM) {
			$this->notORM = new NotORM($this->pdo);
		}
	}

	/**
	 * @return PDO
	 */
	function getPDO() {
		return $this->pdo;
	}

	/**
	 * @return NotORM
	 */
	function getDb() {
		$this->createNotORMIfNeeded();
		return $this->notORM;
	}

	/**
	 * @return NotORM
	 */
	function getWriteDb() {
		return $this->debugDb ?: $this->getDb();
	}

	/**
	 * @return string
	 */
	function generateFakeId() {
		$this->lastFakeID++;
		return '#_FAKE_' . $this->lastFakeID;
	}

	/**
	 * @param string $query
	 * @param array $parameters
	 *
	 * @return void
	 */
	static function echoQuery($query, $parameters) {
		echo '<pre>' . "\n\n" . $query . "\n</pre>";
		if ($parameters) {
			echo "\n<br />Parameters: <pre>" . print_r($parameters, true) . "</pre>\n\n<br /><br />";
		}

	}

}
