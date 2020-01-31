<?php

namespace OndraKoupil\AppTools\Data;

use PDO;

class StructureFromPDO extends Structure {

	function __construct(PDO $pdo, $tableName, $masterColumn, $sortingColumn = '', $idColumn = 'id', $sortingDirection = 'ASC') {

		$dataGetter = function() use ($pdo, $tableName, $masterColumn, $sortingColumn, $idColumn, $sortingDirection) {
			$query = 'SELECT `' . $idColumn . '` as `id`, `' . $masterColumn . '` as `master` from `' . $tableName . '`';
			if ($sortingColumn) {
				$query .= ' ORDER BY `' . $sortingColumn . '` ' . $sortingDirection;
			}
			return $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
		};

		parent::__construct($dataGetter);

	}

}
