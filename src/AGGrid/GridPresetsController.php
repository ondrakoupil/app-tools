<?php

namespace OndraKoupil\AppTools\AGGrid;

use Exception;
use PDO;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Universal slim controller for handling API calls for AGGrid column presets
 */
class GridPresetsController {


	/**
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * @var string
	 */
	protected $gridName;

	/**
	 * @var string
	 */
	protected $tableName;

	function __construct(
		PDO $pdo,
		$tableName,
		$gridName
	) {
		$this->pdo = $pdo;
		$this->gridName = $gridName;
		$this->tableName = $tableName;
	}

	function __invoke(Request $request, Response $response, $args) {
		return $this->get($request, $response, $args);
	}
	
	function get(Request $request, Response $response, $args) {
		$statement = $this->pdo->prepare(
			'
				SELECT
					*
				FROM
					' . $this->tableName . '
				WHERE
					gridName = :gridName
			'
		);

		$res = $statement->execute(array('gridName' => $this->gridName));

		$data = array();

		foreach ($statement as $row) {
			$data[] = array(
				'id'      => $row['id'],
				'name'    => $row['name'],
				'filters' => $row['filters'] ? json_decode($row['filters'], true, 512, JSON_THROW_ON_ERROR) : null,
				'columns' => $row['columns'] ? json_decode($row['columns'], true, 512, JSON_THROW_ON_ERROR) : null,
				'sort'    => $row['sort'] ? json_decode($row['sort'], true, 512, JSON_THROW_ON_ERROR) : null,
			);
		}

		return $response->withJson($data);
	}

	function create(Request $request, Response $response, $args) {

		$name = $request->getParam('name');
		$filters = $request->getParam('filters');
		$sort = $request->getParam('sort');
		$columns = $request->getParam('columns');

		if (!$filters) {
			$filters = null;
		} else {
			$filters = json_encode($filters, JSON_THROW_ON_ERROR);
		}

		if (!$sort) {
			$sort = null;
		} else {
			$sort = json_encode($sort, JSON_THROW_ON_ERROR);
		}

		if (!$columns) {
			$columns = null;
		} else {
			$columns = json_encode($columns, JSON_THROW_ON_ERROR);
		}

		$saved = $this->pdo->prepare(
			'
				INSERT INTO
					' . $this->tableName . '
				(gridName, name, filters, columns, sort)
				VALUES
				(:gridName, :name, :filters, :columns, :sort)
			'
		)->execute(
			array(
				'gridName' => $this->gridName,
				'name'     => $name,
				'filters'  => $filters,
				'columns'  => $columns,
				'sort'     => $sort,
			)
		);

		$id = $this->pdo->lastInsertId();

		$data = array(
			'id'      => $id,
			'name'    => $name,
			'filters' => $filters,
			'sort'    => $sort,
			'columns' => $columns,
		);

		return $response->withJson($data);
	}

	function delete(Request $request, Response $response, $args) {

		$id = $request->getParam('id');

		if (!$id) {
			throw new Exception('No ID was given.');
		}

		$this->pdo->prepare('
			DELETE FROM 
			            ' . $this->tableName . '
			WHERE 
				id = :id 
				AND gridName = :gridName 
		')
			->execute(
				array(
					'id' => $id,
					'gridName' => $this->gridName,
				)
			)
		;

		return $response->withStatus(204);

	}

	function update(Request $request, Response $response, $args) {

		$id = $request->getParam('id');
		$filters = $request->getParam('filters');
		$sort = $request->getParam('sort');
		$columns = $request->getParam('columns');

		if (!$id) {
			throw new Exception('No ID was given.');
		}

		if (!$filters) {
			$filters = null;
		} else {
			$filters = json_encode($filters, JSON_THROW_ON_ERROR);
		}

		if (!$sort) {
			$sort = null;
		} else {
			$sort = json_encode($sort, JSON_THROW_ON_ERROR);
		}

		if (!$columns) {
			$columns = null;
		} else {
			$columns = json_encode($columns, JSON_THROW_ON_ERROR);
		}

		$saved = $this->pdo->prepare(
			'
				UPDATE
					' . $this->tableName . '
				SET 
				    filters = :filters,
				    columns = :columns,
				    sort = :sort
				WHERE
				    id = :id
					AND gridName = :gridName
			'
		)->execute(
			array(
				'id'       => $id,
				'gridName' => $this->gridName,
				'filters'  => $filters,
				'columns'  => $columns,
				'sort'     => $sort,
			)
		);

		$presetStatement = $this->pdo->prepare('SELECT * FROM ' . $this->tableName . ' WHERE id = :id');
		$presetStatement->execute(array('id' => $id));
		$preset = $presetStatement->fetch();

		$data = array(
			'id'      => $preset['id'],
			'name'    => $preset['name'],
			'filters' => $preset['filters'] ? json_decode($preset['filters'], true, 512, JSON_THROW_ON_ERROR) : null,
			'columns' => $preset['columns'] ? json_decode($preset['columns'], true, 512, JSON_THROW_ON_ERROR) : null,
			'sort'    => $preset['sort'] ? json_decode($preset['sort'], true, 512, JSON_THROW_ON_ERROR) : null,
		);

		return $response->withJson($data);
	}


}
