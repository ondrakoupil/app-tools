<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

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

	function expandItem($id, array $data, $specs = null): array;

}
