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
	function afterCreate($id, $data): void;

	function beforeDelete($data): void;
	function afterDelete($data): void;

	function beforeUpdate($id, $data): array;
	function afterUpdate($id, $data): void;

	function beforeClone($id, $data): array;
	function afterClone($originalId, $newId, $data): void;

}
