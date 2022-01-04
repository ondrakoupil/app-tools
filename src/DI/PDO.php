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

	/**
	 * @param DbConnectionSettings $dbSettings
	 *
	 * @return BasePDO
	 */
	static function factory(DbConnectionSettings $dbSettings) {

		$pdo = new BasePDO(
			'mysql:host=' . $dbSettings->host . ';dbname=' . $dbSettings->dbName . ';charset=' . $dbSettings->charset,
			$dbSettings->user,
			$dbSettings->password,
			array(
				BasePDO::ATTR_ERRMODE => BasePDO::ERRMODE_EXCEPTION,
				BasePDO::ATTR_DEFAULT_FETCH_MODE => BasePDO::FETCH_ASSOC,
			)
		);

		return $pdo;

	}

	/**
	 * @param DbConnectionSettings $dbSettings
	 *
	 * @return BasePDO
	 */

	static function sqlsrvFactory(DbConnectionSettings $dbSettings) {

		$pdo = new BasePDO(
			'sqlsrv:Server=' . $dbSettings->host . ';Database=' . $dbSettings->dbName . ';LoginTimeout=3;TrustServerCertificate=1',
			$dbSettings->user,
			$dbSettings->password,
			array(
				BasePDO::ATTR_TIMEOUT => 5,
				BasePDO::ATTR_ERRMODE => BasePDO::ERRMODE_EXCEPTION,
				BasePDO::ATTR_DEFAULT_FETCH_MODE => BasePDO::FETCH_ASSOC,
				BasePDO::SQLSRV_ATTR_ENCODING => BasePDO::SQLSRV_ENCODING_UTF8,
			)
		);

		return $pdo;
	}

}
