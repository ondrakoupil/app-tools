<?php

namespace OndraKoupil\AppTools\DI;

use OndraKoupil\AppTools\AppSettings\DbConnectionSettings;
use PDO as BasePDO;
use Slim\Container;

class PDO {

	/*
	 * Usage:
	 *
	 * PDO::addFactory($container, $container[AppSettings::class]->db);
	 *
	 */

	static function addFactory(Container $container, DbConnectionSettings $dbSettings) {

		$container[BasePDO::class] = function(Container $container) use ($dbSettings) {
			return self::factory($dbSettings);
		};

	}

	static function factory(DbConnectionSettings $dbSettings) {

		$pdo = new BasePDO(
			'mysql:host=' . $dbSettings->host . ';dbname=' . $dbSettings->dbName . ';charset=' . $dbSettings->charset,
			$dbSettings->user,
			$dbSettings->password
		);

		return $pdo;

	}

}
