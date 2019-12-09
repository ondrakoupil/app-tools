<?php

namespace OndraKoupil\AppTools\Config;

use \PDO;

/**
 * Config manager, který svá data ukládá do databáze.
 */
class DatabaseManager extends ManagerWithDefaults {

	/**
	 * @var string
	 */
	protected $idColumn = 'id';

	/**
	 * @var string
	 */
	protected $valueColumn = 'value';

	/**
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var bool
	 */
	protected $hasAlreadyRead = false;

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var array
	 */
	protected $valuesToBeWritten = array();

	/**
	 * @param PDO $pdo
	 * @param string $tableName
	 * @param array $defaults
	 * @param bool $strictMode
	 */
	public function __construct(PDO $pdo, $tableName, $defaults = array(), $strictMode = false) {
		parent::__construct($defaults, $strictMode);
		$this->pdo = $pdo;
		$this->tableName = $tableName;
	}

	/**
	 * @param string $idColumn
	 * @param string $valueColumn
	 *
	 * @return void
	 */
	public function setColumnNames($idColumn, $valueColumn) {
		$this->idColumn = $idColumn;
		$this->valueColumn = $valueColumn;
	}

	/**
	 * @param string $key
	 * @param null $default
	 *
	 * @return mixed
	 * @throws InvalidConfigKeyException
	 */
	public function get($key, $default = null) {
		if (!$this->isValidKey($key)) {
			throw new InvalidConfigKeyException('Invalid config key: ' . $key);
		}
		$this->readIfNeeded();
		if (array_key_exists($key, $this->values)) {
			return $this->values[$key];
		}
		return $this->getDefault($key, $default);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 */
	public function set($key, $value) {
		$this->valuesToBeWritten[$key] = $value;
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function clear($key) {
		$this->valuesToBeWritten = array();
		$this->pdo
			->prepare('DELETE FROM `' . $this->tableName . '` WHERE `' . $this->idColumn. '` = ?')
			->execute(array($key))
		;
	}

	/**
	 * @return void
	 */
	public function write() {
		if ($this->valuesToBeWritten) {
			$args = array();
			$q = '
				INSERT INTO `' . $this->tableName . '` (`' . $this->idColumn. '`, `' . $this->valueColumn. '`) VALUES
			';

			$first = true;
			foreach ($this->valuesToBeWritten as $key => $value) {
				$args[] = $key;
				$args[] = $value;

				if (!$first) {
					$q .= ', ';
				} else {
					$first = false;
				}
				$q .= '(?, ?)';
			}

			$this
				->pdo
				->prepare($q)
				->execute($args)
			;
		}
		$this->valuesToBeWritten = array();
	}


	protected function readIfNeeded() {
		if (!$this->hasAlreadyRead) {
			$this->readNow();
		}
	}

	protected function readNow() {
		$this->values = $this->readValuesFromDb();
	}

	protected function readValuesFromDb() {
		$result = $this->pdo->query('
			SELECT 
			    `' . $this->idColumn. '` as id, `' . $this->valueColumn. '` as value
			FROM 
				`' . $this->tableName . '`
		');
		return $result->fetchAll(PDO::FETCH_KEY_PAIR);
	}

}
