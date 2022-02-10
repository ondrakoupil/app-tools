<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

use NotORM;
use NotORM_Result;

interface EntitySpec {

	/**
	 * @param mixed $id
	 *
	 * @return bool
	 */
	function isFormallyValidId($id): bool;

	/**
	 * @return SlugField[]
	 */
	function getSlugFields(): array;

	function beforeCreate($data): array;
	function afterCreate($id, array $data): void;

	function beforeDelete(array $data): void;
	function afterDelete(array $data): void;

	function beforeUpdate($id, array $data): array;
	function afterUpdate($id, array $data): void;

	function beforeClone($id, array $data): array;
	function afterClone($originalId, $newId, array $data): void;

	function expandItem($id, array $data, $context = null): array;
	function expandManyItems(array $items, $context = null): array;
	
	function getAllItemsRequest(NotORM_Result $request): NotORM_Result;
	function getAllItemsFilter(array $items): array;

}
