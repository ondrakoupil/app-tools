<?php

namespace OndraKoupil\AppTools;

use PDO;

class PDOBuilder {

	/**
	 * Quick create PDO connection without googling how DNS string should look like.
	 * Also sets ERRMODE to exceptions and calls `set names utf8`
	 *
	 * @param string $host
	 * @param string $database
	 * @param string $username
	 * @param string $password
	 *
	 * @return PDO
	 */
	static function createMysqlPDO($host, $database, $username, $password) {
		$pdo = new PDO('mysql:host=' . $host . ';dbname=' . $database, $username, $password);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->query('set names utf8');
		return $pdo;
	}

}
