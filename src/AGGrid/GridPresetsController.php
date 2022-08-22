<?php

namespace OndraKoupil\AppTools\AGGrid;

use Exception;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

	function __invoke(ServerRequestInterface $request, ResponseInterface $response) {
		return $this->get($request, $response);
	}

	function get(ServerRequestInterface $request, ResponseInterface $response) {
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

		$response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
		return $response;
	}

	function create(ServerRequestInterface $request, ResponseInterface $response) {

		$params = $request->getParsedBody();
		$name = $params['name'] ?? null;
		$filters = $params['filters'] ?? null;
		$sort = $params['sort'] ?? null;
		$columns = $params['columns'] ?? null;

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

		$response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
		return $response;
	}

	function delete(ServerRequestInterface $request, ResponseInterface $response) {

		$params = $request->getParsedBody();
		$id = $params['id'] ?? null;

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

	function update(ServerRequestInterface $request, ResponseInterface $response) {

		$params = $request->getParsedBody();
		$id = $params['id'] ?? null;
		$filters = $params['filters'] ?? null;
		$sort = $params['sort'] ?? null;
		$columns = $params['columns'] ?? null;

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

		$response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
		return $response;

	}


}
