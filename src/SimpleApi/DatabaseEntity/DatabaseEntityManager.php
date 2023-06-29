<?php

namespace OndraKoupil\AppTools\SimpleApi\DatabaseEntity;

use Exception;
use InvalidArgumentException;
use NotORM;
use NotORM_Result;
use NotORM_Row;
use OndraKoupil\AppTools\SimpleApi\Entity\DefaultEntity;
use OndraKoupil\AppTools\SimpleApi\Entity\EntitySpec;
use OndraKoupil\AppTools\SimpleApi\EntityManagerInterface;
use OndraKoupil\AppTools\SimpleApi\ItemNotFoundException;
use OndraKoupil\AppTools\SimpleApi\Relations\AutoExpandingParentManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\AutoExpandingRelatedItemsManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\AutoExpandingSubItemsManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\EditableRelatedItemsManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\EntityWithMultiRelationManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\EntityWithParentItemManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\EntityWithSubItemsManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\EntityWithWritableMultiRelationManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\MultiRelationManager;
use OndraKoupil\AppTools\SimpleApi\Relations\SubItemsManager;
use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Strings;

class DatabaseEntityManager
	implements
		EntityManagerInterface,
		EntityWithSubItemsManagerInterface,
		EntityWithParentItemManagerInterface,
		EntityWithWritableMultiRelationManagerInterface,
		AutoExpandingSubItemsManagerInterface,
		AutoExpandingParentManagerInterface,
		AutoExpandingRelatedItemsManagerInterface,
		EditableRelatedItemsManagerInterface
{

	/**
	 * @var NotORM
	 */
	protected $notORM;

	/**
	 * @var string
	 */
	protected $tableName;

	/**
	 * @var EntitySpec
	 */
	protected $spec;

	/**
	 * @var InternalRelationParams[]  Indexed by entity ID
	 */
	protected $childItemManagers = array();

	protected $defaultChildItemEntityId = 'default-not-set';

	/**
	 * @var InternalAutoExpandingParams[] Indexed by context key
	 */
	protected $autoExpandChildItems = array();

	/**
	 * @var InternalRelationParams[]  Indexed by entity ID
	 */
	protected $parentItemManagers = array();

	protected $defaultParentItemEntityId = 'default-not-set';

	/**
	 * @var InternalAutoExpandingParams[] Indexed by context key
	 */
	protected $autoExpandParentItems = array();

	/**
	 * @var InternalMultiRelationParams[]  Indexed by entity ID
	 */
	protected $relatedItemManagers = array();

	protected $defaultRelatedItemEntityId = 'default-not-set';

	/**
	 * @var InternalAutoExpandingParams[] Indexed by context key
	 */
	protected $autoExpandRelatedItems = array();

	/**
	 * @var InternalAutoEditingParams[] Indexed numerically
	 */
	protected $autoEditables = array();

	/**
	 * @var callable [key] => function($objects) {}
	 */
	protected $otherExpanding = array();



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

	function defineSubItemsRelation(SubItemsManager $relationManager, string $childEntityId, $childEntityManagerOrGetter, bool $asDefault = true) {
		if (isset($this->childItemManagers[$childEntityId])) {
			throw new Exception('Relation for sub item entity ' . $childEntityId . ' is already defined!');
		}
		$this->childItemManagers[$childEntityId] = new InternalRelationParams(
			$childEntityId,
			$relationManager,
			$childEntityManagerOrGetter
		);
		if ($asDefault) {
			$this->defaultChildItemEntityId = $childEntityId;
		}
	}

	function setupAutoExpandSubItems(
		$contextKey,
		$childEntityId = '',
		$childItemExpandContext = array(),
		$itemFieldToExpandSubitems = ''
	) {
		if (!$childEntityId) {
			$childEntityId = $this->defaultChildItemEntityId;
		}
		if (!$this->childItemManagers[$childEntityId]) {
			throw new InvalidArgumentException('SubItem relationship with entity ' . $childEntityId . ' is not defined. Call defineSubItemsRelation() first.');
		}
		if ($this->autoExpandRelatedItems[$contextKey] ?? null or $this->autoExpandParentItems[$contextKey] ?? null or $this->autoExpandChildItems[$contextKey] ?? null) {
			throw new InvalidArgumentException('AutoExpanding context key ' . $contextKey . ' is already set up.');
		}
		$this->autoExpandChildItems[$contextKey] = new InternalAutoExpandingParams($contextKey, $childEntityId, $childItemExpandContext, $itemFieldToExpandSubitems);
		$this->autoExpandChildItems[$contextKey]->setupCountParams($contextKey . 'Count');
		$this->autoExpandChildItems[$contextKey]->setupIdsParams($contextKey . 'Ids');
	}

	function defineParentEntityRelation(SubItemsManager $relationManager, string $parentEntityId,  $parentEntityManagerOrGetter, bool $asDefault = true) {
		if (isset($this->parentItemManagers[$parentEntityId])) {
			throw new Exception('Relation for parent entity ' . $parentEntityId . ' is already defined!');
		}
		$this->parentItemManagers[$parentEntityId] = new InternalRelationParams(
			$parentEntityId,
			$relationManager,
			$parentEntityManagerOrGetter
		);

		if ($asDefault) {
			$this->defaultParentItemEntityId = $parentEntityId;
		}
	}

	function setupAutoExpandParent(
		string $contextKey,
		string $parentEntityId = '',
		$parentItemExpandContext = array(),
		$itemFieldToExpandParent = ''
	) {
		if (!$parentEntityId) {
			$parentEntityId = $this->defaultParentItemEntityId;
		}
		if (!$this->parentItemManagers[$parentEntityId]) {
			throw new InvalidArgumentException('Parent item relationship with entity ' . $parentEntityId . ' is not defined. Call defineParentEntityRelation() first.');
		}
		if ($this->autoExpandRelatedItems[$contextKey] ?? null or $this->autoExpandParentItems[$contextKey] ?? null or $this->autoExpandChildItems[$contextKey] ?? null) {
			throw new InvalidArgumentException('AutoExpanding context key ' . $contextKey . ' is already set up.');
		}
		$this->autoExpandParentItems[$contextKey] = new InternalAutoExpandingParams($contextKey, $parentEntityId, $parentItemExpandContext, $itemFieldToExpandParent);
	}


	function defineEntityWithMultiRelation(MultiRelationManager $relationManager, string $otherEntityId, $otherEntityManagerOrGetter, bool $asDefault = true) {
		if (isset($this->relatedItemManagers[$otherEntityId])) {
			throw new Exception('Relation for related multi entity ' . $otherEntityId . ' is already defined!');
		}
		$this->relatedItemManagers[$otherEntityId] = new InternalMultiRelationParams(
			$otherEntityId,
			$relationManager,
			$otherEntityManagerOrGetter
		);

		if ($asDefault) {
			$this->defaultRelatedItemEntityId = $otherEntityId;
		}
	}

	function setupAutoExpandRelatedItems(
		string $contextKey,
		string $otherEntityId = '',
		       $relatedItemExpandContext = array(),
		string $itemFieldToExpandRelatedItems = ''
	) {
		if (!$otherEntityId) {
			$otherEntityId = $this->defaultRelatedItemEntityId;
		}
		if (!$this->relatedItemManagers[$otherEntityId]) {
			throw new InvalidArgumentException('Related item relationship with entity ' . $otherEntityId . ' is not defined. Call defineEntityWithMultiRelation() first.');
		}
		if ($this->autoExpandRelatedItems[$contextKey] ?? null or $this->autoExpandParentItems[$contextKey] ?? null or $this->autoExpandChildItems[$contextKey] ?? null) {
			throw new InvalidArgumentException('AutoExpanding context key ' . $contextKey . ' is already set up.');
		}
		$this->autoExpandRelatedItems[$contextKey] = new InternalAutoExpandingParams($contextKey, $otherEntityId, $relatedItemExpandContext, $itemFieldToExpandRelatedItems);
		$this->autoExpandRelatedItems[$contextKey]->setupCountParams($contextKey . 'Count');
		$this->autoExpandRelatedItems[$contextKey]->setupIdsParams($contextKey . 'Ids');
	}

	function setupEditableRelatedItems(string $keyInInputSet, string $keyInInputAdd = '', string $keyInInputDelete = '', string $keyInInputClear = '', string $otherEntityId = '') {
		if (!$keyInInputAdd) {
			$keyInInputAdd = $keyInInputSet . 'Add';
		}
		if (!$keyInInputDelete) {
			$keyInInputDelete = $keyInInputSet . 'Delete';
		}
		if (!$keyInInputClear) {
			$keyInInputClear = $keyInInputSet . 'Clear';
		}
		$params = new InternalAutoEditingParams($keyInInputSet, $keyInInputAdd, $keyInInputDelete, $keyInInputClear, $otherEntityId);
		$this->autoEditables[] = $params;
	}

	function setupExpanding(string $contextKey, callable $callback) {
		$this->otherExpanding[$contextKey] = $callback;
	}

	function getAllIds($restriction = null, $context = null): array {
		$table = $this->tableName;
		$allIdsRequest = $this->notORM->$table();
		$allIdsRequest = $this->spec->getAllItemsRequest($allIdsRequest, $context);
		$allIdsRequest->select('id');
		$r = array();
		foreach ($allIdsRequest as $row) {
			$r[] = $row['id'];
		}
		return $r;
	}

	/**
	 * @param string $id
	 *
	 * @return NotORM_Row
	 * @throws ItemNotFoundException
	 * @throws InvalidArgumentException
	 */
	function getItemRow(string $id): NotORM_Row {
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
	function getManyItemRows(array $ids): array {
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

	protected function expandItem(string $id, array $item, $context = null): array {
		$expandedFromSpec = $this->spec->expandItem($id, $item, $context);
		if ($context) {
			foreach ($this->autoExpandChildItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$expandedFromSpec[$autoExpandData->field] = $this->getSubItemsByIdExpanded($id, $autoExpandData->expandContext, $autoExpandData->entityId);
				}
				if ($autoExpandData->countContextKey) {
					if ($context[$autoExpandData->countContextKey] ?? false) {
						$expandedFromSpec[$autoExpandData->countField] = count($this->getSubItemsById($id, $autoExpandData->entityId));
					}
				}
				if ($autoExpandData->idsContextKey) {
					if ($context[$autoExpandData->idsContextKey] ?? false) {
						$expandedFromSpec[$autoExpandData->idsField] = $this->getSubItemsById($id, $autoExpandData->entityId);
					}
				}
			}
			foreach ($this->autoExpandParentItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$expandedFromSpec[$autoExpandData->field] = $this->getParentByIdExpanded($id, $autoExpandData->expandContext, $autoExpandData->entityId);
				}
			}
			foreach ($this->autoExpandRelatedItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$expandedFromSpec[$autoExpandData->field] = $this->getRelatedItemsByIdExpanded($id, $autoExpandData->expandContext, $autoExpandData->entityId);
				}
				if ($autoExpandData->countContextKey) {
					if ($context[$autoExpandData->countContextKey] ?? false) {
						$expandedFromSpec[$autoExpandData->countField] = count($this->getRelatedItemsById($id, $autoExpandData->entityId));
					}
				}
				if ($autoExpandData->idsContextKey) {
					if ($context[$autoExpandData->idsContextKey] ?? false) {
						$expandedFromSpec[$autoExpandData->idsField] = $this->getRelatedItemsById($id, $autoExpandData->entityId);
					}
				}
			}
			foreach ($this->otherExpanding as $contextKey => $callable) {
				if ($context[$contextKey] ?? false) {
					$expandedFromSpec = $callable(array($expandedFromSpec))[0];
				}
			}
		}
		return $expandedFromSpec;
	}

	protected function expandManyItems(array $items, $context = null): array {
		$expandedFromSpec = $this->spec->expandManyItems($items, $context);
		if ($context) {
			foreach ($this->autoExpandChildItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$ids = Arrays::valuePicker($items, 'id');
					$subItems = $this->getManySubItemsByIdExpanded($ids, $autoExpandData->expandContext, $autoExpandData->entityId);
					foreach ($expandedFromSpec as $index => $item) {
						$expandedFromSpec[$index][$autoExpandData->field] = $subItems[$item['id']] ?? array();
					}
				}
				if ($autoExpandData->countContextKey) {
					if ($context[$autoExpandData->countContextKey] ?? false) {
						$ids = Arrays::valuePicker($items, 'id');
						$allSubItemsItemsCount = $this->getManySubItemsById($ids, $autoExpandData->entityId);
						foreach ($expandedFromSpec as $index => $item) {
							$expandedFromSpec[$index][$autoExpandData->countField] = (($allSubItemsItemsCount[$item['id']] ?? false) ? count($allSubItemsItemsCount[$item['id']]) : 0);
						}
					}
				}
				if ($autoExpandData->idsContextKey) {
					if ($context[$autoExpandData->idsContextKey] ?? false) {
						$ids = Arrays::valuePicker($items, 'id');
						$allSubItemsItemIds = $this->getManySubItemsById($ids, $autoExpandData->entityId);
						foreach ($expandedFromSpec as $index => $item) {
							$expandedFromSpec[$index][$autoExpandData->idsField] = (($allSubItemsItemIds[$item['id']] ?? false) ? $allSubItemsItemIds[$item['id']] : array());
						}
					}
				}
			}
			foreach ($this->autoExpandParentItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$ids = Arrays::valuePicker($items, 'id');
					$subItems = $this->getManyParentsByIdExpanded($ids, $autoExpandData->expandContext, $autoExpandData->entityId);
					foreach ($expandedFromSpec as $index => $item) {
						$expandedFromSpec[$index][$autoExpandData->field] = $subItems[$item['id']] ?? array();
					}
				}
			}
			foreach ($this->autoExpandRelatedItems as $contextKey => $autoExpandData) {
				if ($context[$contextKey] ?? false) {
					$ids = Arrays::valuePicker($items, 'id');
					$allRelatedItems = $this->getManyRelatedItemsByIdExpanded($ids, $autoExpandData->expandContext, $autoExpandData->entityId);
					foreach ($expandedFromSpec as $index => $item) {
						$expandedFromSpec[$index][$autoExpandData->field] = $allRelatedItems[$item['id']] ?? array();
					}
				}
				if ($autoExpandData->countContextKey) {
					if ($context[$autoExpandData->countContextKey] ?? false) {
						$ids = Arrays::valuePicker($items, 'id');
						$allRelatedItemsCount = $this->getManyRelatedItemsById($ids, $autoExpandData->entityId);
						foreach ($expandedFromSpec as $index => $item) {
							$expandedFromSpec[$index][$autoExpandData->countField] = (($allRelatedItemsCount[$item['id']] ?? false) ? count($allRelatedItemsCount[$item['id']]) : 0);
						}
					}
				}
				if ($autoExpandData->idsContextKey) {
					if ($context[$autoExpandData->idsContextKey] ?? false) {
						$ids = Arrays::valuePicker($items, 'id');
						$allRelatedItemsItemIds = $this->getManyRelatedItemsById($ids, $autoExpandData->entityId);
						foreach ($expandedFromSpec as $index => $item) {
							$expandedFromSpec[$index][$autoExpandData->idsField] = (($allRelatedItemsItemIds[$item['id']] ?? false) ? $allRelatedItemsItemIds[$item['id']] : array());
						}
					}
				}
			}
			foreach ($this->otherExpanding as $contextKey => $callable) {
				if ($context[$contextKey] ?? false) {
					$expandedFromSpec = $callable($expandedFromSpec);
				}
			}

		}
		return $expandedFromSpec;
	}

	protected function prepareDataArrayForSaving($data) {
		foreach ($this->autoEditables as $editable) {
			if (array_key_exists($editable->keyDelete, $data)) {
				unset($data[$editable->keyDelete]);
			}
			if (array_key_exists($editable->keySet, $data)) {
				unset($data[$editable->keySet]);
			}
			if (array_key_exists($editable->keyAdd, $data)) {
				unset($data[$editable->keyAdd]);
			}
			if (array_key_exists($editable->keyClear, $data)) {
				unset($data[$editable->keyClear]);
			}
		}
		return $data;
	}

	function getItem(string $id, $context = null): array {
		$basicData = iterator_to_array($this->getItemRow($id));
		return $this->expandItem($id, $basicData, $context);
	}

	function getManyItems(array $ids, $context = null): array {
		$allData = array_values(array_map('iterator_to_array', $this->getManyItemRows($ids)));
		return $this->expandManyItems($allData, $context);
	}

	function getAllItems($context = null, $restriction = null): array {
		$table = $this->tableName;
		$request = $this->notORM->$table();
		$requestProcessed = $this->spec->getAllItemsRequest($request, $context);
		$requestProcessed = $this->applyRestrictionToDbRequest($restriction, $requestProcessed);
		$items = array_values(array_map('iterator_to_array', iterator_to_array($requestProcessed)));
		$itemsFiltered = array_filter($items, array($this->spec, 'getAllItemsFilter'));
		return $this->expandManyItems($itemsFiltered, $context);
	}

	function exists(string $id): bool {
		try {
			$this->getItemRow($id);
			return true;
		} catch (ItemNotFoundException $e) {
			return false;
		}
	}

	function existsAllOf(array $ids): bool {
		try {
			$this->getManyItemRows($ids);
			return true;
		} catch (ItemNotFoundException $e) {
			return false;
		}
	}

	function createItem(array $data): array {
		$table = $this->tableName;
		$data = $this->processSlugFields($data);
		$data = $this->spec->beforeCreate($data);
		$dataToInsert = $this->prepareDataArrayForSaving($data);
		$inserted = $this->notORM->$table()->insert($dataToInsert);
		$insertedItem = iterator_to_array($this->getItemRow($inserted['id']));
		$this->spec->afterCreate($inserted['id'], $insertedItem);
		$this->processAutoEditingRelationFromInput($inserted['id'], $data);
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
		$dataToUpdate = $this->prepareDataArrayForSaving($data);
		$is->update($dataToUpdate);
		$result = $this->getItemRow($id);

		$this->spec->afterUpdate($id, iterator_to_array($result));

		$this->processAutoEditingRelationFromInput($id, $data);
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
		$original = $this->spec->beforeClone($id, $original);
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

	/* SubItems management and functions */

	protected function getChildItemRelationManagerById($childEntityId = ''): SubItemsManager {
		if (!$childEntityId) {
			$childEntityId = $this->defaultChildItemEntityId;
		}
		if (!isset($this->childItemManagers[$childEntityId])) {
			throw new Exception('Subitems relation for entity ' . $childEntityId . ' is not defined.');
		}
		return $this->childItemManagers[$childEntityId]->relationManager;
	}

	protected function getChildItemEntityManagerById($childEntityId = ''): EntityManagerInterface {
		if (!$childEntityId) {
			$childEntityId = $this->defaultChildItemEntityId;
		}
		if (!isset($this->childItemManagers[$childEntityId])) {
			throw new Exception('Subitems relation for entity ' . $childEntityId . ' is not defined.');
		}
		return $this->childItemManagers[$childEntityId]->getEntityManager();
	}

	/**
	 * @param string $childEntityId
	 * @param string $id
	 *
	 * @return string[]
	 * @throws Exception
	 */
	function getSubItemsById(string $id, string $childEntityId = ''): array {
		return $this->getChildItemRelationManagerById($childEntityId)->getChildrenOf($id);
	}

	/**
	 * @param string $childEntityId
	 * @param string[] $ids
	 *
	 * @return array [parentId] => childIds[]
	 * @throws Exception
	 */
	function getManySubItemsById(array $ids, string $childEntityId = ''): array {
		return $this->getChildItemRelationManagerById($childEntityId)->getChildrenOfMany($ids);
	}

	function getSubItemsByIdExpanded(string $id, $expandParts = null, string $childEntityId = ''): array {
		$ids = $this->getSubItemsById($id, $childEntityId);
		$entityManager = $this->getChildItemEntityManagerById($childEntityId);
		return $entityManager->getManyItems($ids, $expandParts);
	}

	function getManySubItemsByIdExpanded(array $ids, $expandParts = null, string $childEntityId = ''): array {
		$subItemsAsIds = $this->getManySubItemsById($ids, $childEntityId);
		$entityManager = $this->getChildItemEntityManagerById($childEntityId);
		$idsOfAllRequiredSubItemsInverted = array();
		foreach ($subItemsAsIds as $subItemIds) {
			if ($subItemIds) {
				foreach ($subItemIds as $subItemId) {
					$idsOfAllRequiredSubItemsInverted[$subItemId] = true;
				}
			}
		}
		$idsOfAllRequiredSubItems = array_keys($idsOfAllRequiredSubItemsInverted);
		$allRequiredSubItems = $entityManager->getManyItems($idsOfAllRequiredSubItems, $expandParts);
		$allRequiredSubItemsById = array();
		foreach ($allRequiredSubItems as $subitem) {
			$allRequiredSubItemsById[$subitem['id']] = $subitem;
		}

		$returned = array();
		foreach ($subItemsAsIds as $parentId => $subItemsIds) {
			$returned[$parentId] = array_map(function($id) use ($allRequiredSubItemsById) { return $allRequiredSubItemsById[$id]; }, $subItemsIds);
		}
		return $returned;
	}

	function preloadManySubItems(array $ids, string $childEntityId = ''): void {
		$this->getChildItemRelationManagerById($childEntityId)->preloadManyParents($ids);
	}

	function preloadAllSubItems(string $childEntityId = ''): void {
		$this->getChildItemRelationManagerById($childEntityId)->preloadAllParents();
	}


	/* Parent item management and function s*/

	protected function getParentItemRelationManagerById($parentEntityId = ''): SubItemsManager {
		if (!$parentEntityId) {
			$parentEntityId = $this->defaultParentItemEntityId;
		}
		if (!isset($this->parentItemManagers[$parentEntityId])) {
			throw new Exception('Parent relation for entity ' . $parentEntityId . ' is not defined.');
		}
		return $this->parentItemManagers[$parentEntityId]->relationManager;
	}

	protected function getParentItemEntityManagerById($parentEntityId = ''): EntityManagerInterface {
		if (!$parentEntityId) {
			$parentEntityId = $this->defaultParentItemEntityId;
		}
		if (!isset($this->parentItemManagers[$parentEntityId])) {
			throw new Exception('Parent relation for entity ' . $parentEntityId . ' is not defined.');
		}
		return $this->parentItemManagers[$parentEntityId]->getEntityManager();
	}

	/**
	 * @param string $parentEntityId
	 * @param string $id
	 * @return string
	 * @throws Exception
	 */
	function getParentById(string $id, string $parentEntityId = ''): string {
		return $this->getParentItemRelationManagerById($parentEntityId)->getParentOf($id);
	}

	/**
	 * @param string $parentEntityId
	 * @param string[] $ids
	 *
	 * @return array [childId] => parentId
	 * @throws Exception
	 */
	function getManyParentsById(array $ids, string $parentEntityId = ''): array {
		return $this->getParentItemRelationManagerById($parentEntityId)->getParentOfMany($ids);
	}

	function getParentByIdExpanded(string $id, $expandParts = null, string $parentEntityId = ''): array {
		$id = $this->getParentById($id, $parentEntityId);
		$entityManager = $this->getParentItemEntityManagerById($parentEntityId);
		return $entityManager->getItem($id, $expandParts);
	}

	function getManyParentsByIdExpanded(array $ids, $expandParts = null, string $parentEntityId = ''): array {
		$parentItemsAsId = $this->getManyParentsById($ids, $parentEntityId);
		$entityManager = $this->getParentItemEntityManagerById($parentEntityId);
		$idsOfAllRequiredParentsInverted = array();
		foreach ($parentItemsAsId as $parentId) {
			if ($parentId) {
				$idsOfAllRequiredParentsInverted[$parentId] = true;
			}
		}
		$idsOfAllRequiredParents = array_keys($idsOfAllRequiredParentsInverted);
		$allRequiredParents = $entityManager->getManyItems($idsOfAllRequiredParents, $expandParts);
		$allRequiredParentsByIds = array();
		foreach ($allRequiredParents as $parent) {
			$allRequiredParentsByIds[$parent['id']] = $parent;
		}

		$returned = array();
		foreach ($parentItemsAsId as $childId => $parentId) {
			if ($parentId) {
				$returned[$childId] = $allRequiredParentsByIds[$parentId];
			} else {
				$returned[$childId] = null;
			}
		}
		return $returned;
	}

	function preloadManyParents(array $ids, string $parentEntityId = ''): void {
		$this->getParentItemRelationManagerById($parentEntityId)->preloadManyChildren($ids);
	}

	function preloadAllParents(string $parentEntityId = ''): void {
		$this->getParentItemRelationManagerById($parentEntityId)->preloadAllChildren();
	}

	// Multi-relations items

	protected function getRelatedItemsRelationManagerById($otherEntityId = ''): MultiRelationManager {
		if (!$otherEntityId) {
			$otherEntityId = $this->defaultRelatedItemEntityId;
		}
		if (!isset($this->relatedItemManagers[$otherEntityId])) {
			throw new Exception('Relation for entity ' . $otherEntityId . ' is not defined.');
		}
		return $this->relatedItemManagers[$otherEntityId]->relationManager;
	}

	protected function getRelatedItemEntityManagerById($otherEntityId = ''): EntityManagerInterface {
		if (!$otherEntityId) {
			$otherEntityId = $this->defaultRelatedItemEntityId;
		}
		if (!isset($this->relatedItemManagers[$otherEntityId])) {
			throw new Exception('Relation for entity ' . $otherEntityId . ' is not defined.');
		}
		return $this->relatedItemManagers[$otherEntityId]->getEntityManager();
	}

	function getRelatedItemsById(string $id, string $otherEntityId = ''): array {
		return $this->getRelatedItemsRelationManagerById($otherEntityId)->get($id);
	}

	function getRelatedItemsByIdExpanded(string $id, $expandParts = null, string $otherEntityId = ''): array {
		$ids = $this->getRelatedItemsById($id, $otherEntityId);
		$entityManager = $this->getRelatedItemEntityManagerById($otherEntityId);
		return $entityManager->getManyItems($ids, $expandParts);
	}

	function getManyRelatedItemsById(array $ids, string $otherEntityId = ''): array {
		return $this->getRelatedItemsRelationManagerById($otherEntityId)->getMany($ids);
	}

	function getManyRelatedItemsByIdExpanded(array $ids, $expandParts = null, string $otherEntityId = ''): array {
		$relatedItemsAsIds = $this->getManyRelatedItemsById($ids, $otherEntityId);
		$entityManager = $this->getRelatedItemEntityManagerById($otherEntityId);
		$idsOfAllRequiredItemsInverted = array();
		foreach ($relatedItemsAsIds as $relatedItemsIds) {
			if ($relatedItemsIds) {
				foreach ($relatedItemsIds as $relatedItemsId) {
					$idsOfAllRequiredItemsInverted[$relatedItemsId] = true;
				}
			}
		}
		$idsOfAllRequiredItems = array_keys($idsOfAllRequiredItemsInverted);
		$allRequiredItems = $entityManager->getManyItems($idsOfAllRequiredItems, $expandParts);
		$allRequiredItemsById = array();
		foreach ($allRequiredItems as $item) {
			$allRequiredItemsById[$item['id']] = $item;
		}

		$returned = array();
		foreach ($relatedItemsAsIds as $thisId => $otherIds) {
			$returned[$thisId] = array_map(function($id) use ($allRequiredItemsById) { return $allRequiredItemsById[$id]; }, $otherIds);
		}
		return $returned;
	}

	function preloadManyRelatedItems(array $ids, string $otherEntityId = ''): void {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->preloadMany($ids);
	}

	function preloadAllRelatedItems(string $otherEntityId = ''): void {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->preloadAll();
	}

	function setRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '') {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->set($id, $relatedIds);
	}

	function addRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '') {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->add($id, $relatedIds);
	}

	function deleteRelatedItemsForId(string $id, array $relatedIds, $otherEntityId = '') {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->delete($id, $relatedIds);
	}

	function clearRelatedItemsForId(string $id, $otherEntityId = '') {
		$this->getRelatedItemsRelationManagerById($otherEntityId)->clear($id);
	}

	protected function processAutoEditingRelationFromInput(string $itemId, array $inputData) {
		foreach ($this->autoEditables as $editable) {
			if (array_key_exists($editable->keyClear, $inputData)) {
				$this->clearRelatedItemsForId($itemId, $editable->entityId);
			} elseif (array_key_exists($editable->keySet, $inputData)) {
				$this->setRelatedItemsForId($itemId, $inputData[$editable->keySet], $editable->entityId);
			} else {
				if (array_key_exists($editable->keyAdd, $inputData) and $inputData[$editable->keyAdd]) {
					$this->addRelatedItemsForId($itemId, $inputData[$editable->keyAdd], $editable->entityId);
				}
				if (array_key_exists($editable->keyDelete, $inputData) and $inputData[$editable->keyDelete]) {
					$this->deleteRelatedItemsForId($itemId, $inputData[$editable->keyDelete], $editable->entityId);
				}
			}
		}

	}


	protected function applyRestrictionToDbRequest($restriction, NotORM_Result $result) {
		if (!$restriction) {
			return $result;
		}

		if (is_callable($restriction)) {
			$restrictedResult = $restriction($result);
			if (!($restrictedResult instanceof NotORM_Result)) {
				throw new Exception('Result of restriction must be a NotORM_Result!');
			}
			return $restrictedResult;
		}

		if (is_array($restriction)) {
			return $result->where($restriction);
		}

		throw new Exception('Restriction must be a callable or an array passable to NotORM_Result->where()!');

	}

}
