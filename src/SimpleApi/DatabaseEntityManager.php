<?php

namespace OndraKoupil\AppTools\SimpleApi;

use NotORM;
use OndraKoupil\Tools\Strings;

class DatabaseEntityManager implements EntityManagerInterface {

	/**
	 * @var NotORM
	 */
	protected $notORM;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var DatabaseEntitySpecification|null
	 */
	protected $spec;

	function __construct(
		NotORM                      $notORM,
		string                      $tableName,
		DatabaseEntitySpecification $spec = null
	) {

		$this->notORM = $notORM;
		$this->tableName = $tableName;
		$this->spec = $spec;
	}


	function getAllItems(): array {
		$table = $this->tableName;

		return array_values(array_map('iterator_to_array', iterator_to_array($this->notORM->$table())));
	}

	function createItem(array $data): array {
		$table = $this->tableName;

		if ($this->spec and $this->spec->beforeSaveCallback) {
			$c = $this->spec->beforeSaveCallback;
			$data = $c(null, $data);
		}
		if ($this->spec and $this->spec->uniqueFields) {
			foreach ($this->spec->uniqueFields as $uniqueField) {
				$data[$uniqueField] = $this->findUnusedValue($uniqueField, $data[$uniqueField]);
			}
		}
		if ($this->spec and $this->spec->uniqueOrEmptyFields) {
			foreach ($this->spec->uniqueOrEmptyFields as $uniqueField) {
				$data[$uniqueField] = null;
			}
		}
		$inserted = $this->notORM->$table()->insert($data);

		return iterator_to_array($inserted);
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function deleteItem(string $id): void {
		$table = $this->tableName;
		$is = $this->notORM->$table()->where('id', $id)->fetch();
		if ($is) {
			$is->delete();
		}
		throw new ItemNotFoundException();
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function updateItem(string $id, array $data): void {
		$table = $this->tableName;
		if (isset($data['id'])) {
			$data['id'] = null;
		}
		if ($this->spec and $this->spec->beforeSaveCallback) {
			$c = $this->spec->beforeSaveCallback;
			$data = $c($id, $data);
		}

		$is = $this->notORM->$table()->where('id', $id)->fetch();
		if ($is) {
			$is->update($data);
		}
		throw new ItemNotFoundException();
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function cloneItem(string $id): array {
		$table = $this->tableName;
		$is = $this->notORM->$table()->where('id', $id)->fetch();

		if ($is) {

			$is = iterator_to_array($is);
			$is['id'] = null;

			if ($this->spec and $this->spec->beforeSaveCallback) {
				$c = $this->spec->beforeSaveCallback;
				$is = $c(null, $is);
			}
			if ($this->spec and $this->spec->uniqueFields) {
				foreach ($this->spec->uniqueFields as $uniqueField) {
					$is[$uniqueField] = $this->findUnusedValue($uniqueField, $is[$uniqueField]);
				}
			}
			if ($this->spec and $this->spec->uniqueOrEmptyFields) {
				foreach ($this->spec->uniqueOrEmptyFields as $uniqueField) {
					$is[$uniqueField] = null;
				}
			}

			$created = $this->notORM->$table()->insert($is);

			return iterator_to_array($created);
		}
		throw new ItemNotFoundException();
	}


	protected function findUnusedValue(string $field, string $requested) {
		$table = $this->tableName;

		if (!$requested) {
			return Strings::randomString(16, true);
		}

		if (preg_match('~^(.+)-[0-9]+$~', $requested, $matches)) {
			$requested = $matches[1];
		}

		$isThere = $this->notORM->$table()->where($field, $requested)->fetch();
		if (!$isThere) {
			return $requested;
		}
		$allValues = array_fill_keys(
			array_values(
				array_map(
					function($i) use ($field) { return $i[$field]; },
					iterator_to_array(
						$this->notORM->$table()->select($field)
					)
				)
			),
			true
		);

		for ($i = 2; $i < 100; $i++) {
			$tested = $requested . '-' . $i;
			if (!isset($allValues[$tested])) {
				return $tested;
			}
		}

		return Strings::randomString(16, true);


	}

}
