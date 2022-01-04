<?php

namespace OndraKoupil\AppTools;

use OndraKoupil\AppTools\AppSettings\DbConnectionSettings;
use PDO;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;

class BackupsManager {

	private $backupsDir;

	protected $ignoredTables = array();

	private $dsn;

	private $user;

	private $pass;

	/**
	 * @param string $backupsDir
	 * @param DbConnectionSettings $dbSettings
	 *
	 * @return BackupsManager
	 */
	static function createUsingDbConnectionSettings($backupsDir, DbConnectionSettings $dbSettings): BackupsManager {
		$dsn = 'mysql:host=' . $dbSettings->host . ';dbname=' . $dbSettings->dbName . ';charset=' . $dbSettings->charset;
		return new BackupsManager($backupsDir, $dsn, $dbSettings->user, $dbSettings->password);
	}

	function __construct($backupsDir, $dsn, $user, $pass) {
		$this->backupsDir = $backupsDir;
		$this->dsn = $dsn;
		$this->user = $user;
		$this->pass = $pass;
	}

	function addIgnoredTable($tableName) {
		$this->ignoredTables[] = $tableName;
	}

	/**
	 * @return string Path to file with the dump
	 */
	function createBackup(): string {

		$hash = substr(md5(rand(10000,99999) . time()), 5, 6);

		$dumpInto = $this->backupsDir . '/' . 'backup-' . date('Y-m-d-H-i-s') . '-' . $hash . '.sql';

		$dump = new Dump();
		$dump
			->dsn($this->dsn)
			->user($this->user)
			->pass($this->pass)
			->tmp($this->backupsDir)
			->file($dumpInto)
		;

		if ($this->ignoredTables) {
			$dump->ignore($this->ignoredTables);
		}

		new Export($dump);

		@chmod($dumpInto, 0666);

		return $dumpInto;

	}

	/**
	 * @param int $keepHours
	 *
	 * @return string[] List of dumps that were deleted
	 */
	function cleanOldBackups($keepHours = 96) {

		$deleted = array();
		$files = glob($this->backupsDir . '/*.sql');
		if ($files) {
			$now = time();
			foreach ($files as $file) {
				if ($now - filemtime($file) > $keepHours * 3600) {
					unlink($file);
					$deleted[] = $file;
				}
			}
		}

		return $deleted;

	}


}
