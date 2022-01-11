<?php

namespace OndraKoupil\AppTools\SimpleApi;

use InvalidArgumentException;
use NotORM;
use NotORM_Row;
use OndraKoupil\AppTools\SimpleApi\Entity\DefaultEntity;
use OndraKoupil\AppTools\SimpleApi\Entity\EntitySpec;
use OndraKoupil\AppTools\SimpleApi\Entity\SlugField;
use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Strings;

class DatabaseEntityManager implements EntityManagerInterface {

	protected NotORM $notORM;

	protected string $tableName;

	protected EntitySpec $spec;

	function __construct(
		NotORM     $notORM,
		string     $tableName,
		EntitySpec $spec = null
	) {

		$this->notORM = $notORM;
		$this->tableName = $tableName;
		
		if ($spec) {
			$this->spec = $spec;
		} else {
			$this->spec = new DefaultEntity();
		}
	}

	/**
	 * @param string $id
	 *
	 * @return NotORM_Row
	 * @throws ItemNotFoundException
	 * @throws InvalidArgumentException
	 */
	protected function getItemRow(string $id): NotORM_Row {
		if (!$this->spec->isFormallyValidId($id)) {
			throw new InvalidArgumentException('Invalid ID ' . $id);
		}
		$table = $this->tableName;
		$item = $this->notORM->$table()->where('id', $id)->fetch();
		if (!$item) {
			throw new ItemNotFoundException($id);
		}
		return $item;
	}

	/**
	 * @param string[] $ids
	 *
	 * @return NotORM_Row[]
	 * @throws ItemNotFoundException
	 * @throws InvalidArgumentException
	 */
	protected function getManyItemRows(array $ids): array {
		foreach ($ids as $id) {
			if (!$this->spec->isFormallyValidId($id)) {
				throw new InvalidArgumentException('Invalid ID ' . $id);
			}
		}
		$table = $this->tableName;
		$items = $this->notORM->$table()->where('id', $ids)->fetchPairs('id');
		foreach ($ids as $id) {
			if (!isset($items[$id])) {
				throw new ItemNotFoundException($id);
			}
		}
		return $items;
	}

	function getItem(string $id, $context = null): array {
		$basicData = iterator_to_array($this->getItemRow($id));
		return $this->spec->expandItem($id, $basicData, $context);
	}

	function getManyItems(array $ids, $context = null): array {
		$allData = array_values(array_map('iterator_to_array', $this->getManyItemRows($ids)));
		return $this->spec->expandManyItems($allData, $context);
	}

	function getAllItems($context = null): array {
		$table = $this->tableName;
		$items = array_values(array_map('iterator_to_array', iterator_to_array($this->notORM->$table())));
		return $this->spec->expandManyItems($items, $context);
	}

	function createItem(array $data): array {
		$table = $this->tableName;
		$data = $this->processSlugFields($data);
		$data = $this->spec->beforeCreate($data);
		$inserted = $this->notORM->$table()->insert($data);
		$insertedItem = iterator_to_array($this->getItemRow($inserted['id']));
		$this->spec->afterCreate($inserted['id'], $insertedItem);
		return $insertedItem;
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function deleteItem(string $id): void {
		if (!$this->spec->isFormallyValidId($id)) {
			throw new InvalidArgumentException('Invalid ID ' . $id);
		}
		$deletedRow = $this->getItemRow($id);
		$deletedArray = iterator_to_array($deletedRow);
		$this->spec->beforeDelete($deletedArray);
		$deletedRow->delete();
		$this->spec->afterDelete($deletedArray);
	}

	/**
	 * @param array $id
	 *
	 * @return void
	 * @throws ItemNotFoundException
	 */
	function deleteManyItems(array $id): void {
		$items = array();
		foreach ($id as $itemId) {
			$items[] = $this->getItemRow($itemId);
		}
		foreach ($items as $item) {
			$this->spec->beforeDelete(iterator_to_array($item));
		}
		$table = $this->tableName;
		$this->notORM->$table()->where('id', $id)->delete();
		foreach ($items as $item) {
			$this->spec->afterDelete(iterator_to_array($item));
		}
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function updateItem(string $id, array $data): void {
		if (!$this->spec->isFormallyValidId($id)) {
			throw new InvalidArgumentException('Invalid ID ' . $id);
		}
		if (isset($data['id'])) {
			$data['id'] = null;
		}
		$data = $this->spec->beforeUpdate($id, $data);
		$is = $this->getItemRow($id);
		$data = $this->processSlugFields($data, $id);

		$is->update($data);
		$result = $this->getItemRow($id);

		$this->spec->afterUpdate($id, iterator_to_array($result));
	}

	/**
	 * @throws ItemNotFoundException
	 */
	function cloneItem(string $id): array {
		if (!$this->spec->isFormallyValidId($id)) {
			throw new InvalidArgumentException('Invalid ID ' . $id);
		}
		$table = $this->tableName;
		$original = iterator_to_array($this->getItemRow($id));
		$original['id'] = null;
		$this->clearSlugFields($original);
		$original = $this->processSlugFields($original);
		$this->spec->beforeClone($id, $original);
		$created = iterator_to_array($this->notORM->$table()->insert($original));
		$this->spec->afterClone($id, $created['id'], $created);
		return $created;
	}

	/**
	 * Return data item with all slug fields as empty strings.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function clearSlugFields(array $data): array {
		foreach ($this->spec->getSlugFields() as $slugField) {
			$data[$slugField->fieldName] = '';
		}

		return $data;
	}

	/**
	 * For all slug fields, check if value is unique and if not, generate new value that is unique.
	 *
	 * @param array $data
	 * @param string $currentId If updating existing item, pass here its ID so that its values will be considered
	 *     unique.
	 * @param string[] $onlyTheseSlugFields If filled, only these slug fields will be processed.
	 *
	 * @return array
	 */
	protected function processSlugFields(array $data, string $currentId = '', array $onlyTheseSlugFields = array()) {

		$fieldsToProcess = $this->spec->getSlugFields();
		if ($onlyTheseSlugFields) {
			$fieldsToProcess = array_filter($fieldsToProcess, function ($slugField) use ($onlyTheseSlugFields) {
				return in_array($slugField->fieldName, $onlyTheseSlugFields);
			});
		}

		$existingData = null;

		foreach ($fieldsToProcess as $slugField) {

			if ($slugField->syncAfterCreating) {
				$data[$slugField->fieldName] = '';
			}

			if ($data[$slugField->fieldName] ?? '') {
				// Check if is unique
				$isUsed = $this->checkIfValueIsUsed($slugField->fieldName, $data[$slugField->fieldName], $currentId);
				if (!$isUsed) {
					continue;
				}
			}

			// Find unique value
			$valueBase = array();
			foreach ($slugField->basedOnFields as $basedOnField) {
				if (isset($data[$basedOnField]) ?? '') {
					$valueBase[] = $data[$basedOnField] ?? '';
				} else {
					if ($currentId) {
						if (!$existingData) {
							$existingData = $this->getItemRow($currentId);
						}
						$valueBase[] = $existingData[$basedOnField] ?? '';
					}
				}
			}
			$valueBaseString = implode(' ', $valueBase);

			$value = Strings::webalize($valueBaseString);
			$value = $this->findUnusedValue($slugField->fieldName, $value, $currentId);
			$data[$slugField->fieldName] = $value;

		}

		return $data;
	}

	/**
	 * Check if value is unique
	 *
	 * @param string $field
	 * @param string $value
	 * @param string $currentId
	 *
	 * @return bool
	 */
	protected function checkIfValueIsUsed(string $field, string $value, string $currentId = ''): bool {
		$table = $this->tableName;
		$isThereReq = $this->notORM->$table()->select('id')->where($field, $value);
		if ($currentId) {
			$isThereReq->where('id != ?', $currentId);
		}
		$isThere = $isThereReq->fetch();

		return !!$isThere;
	}

	protected function findUnusedValue(string $field, string $requested, string $currentId = ''): string {
		$table = $this->tableName;

		if (!$requested) {
			return Strings::randomString(16, true);
		}

		if (!$this->checkIfValueIsUsed($field, $requested, $currentId)) {
			return $requested;
		}

		if (preg_match('~^(.+)-[0-9]{1,2}$~', $requested, $matches)) {
			$requested = $matches[1];
		}

		$allValues = array_fill_keys(
			array_values(
				array_map(
					function ($i) use ($field) {
						return $i[$field];
					},
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
