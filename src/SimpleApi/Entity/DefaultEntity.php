<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

class DefaultEntity implements EntitySpec {

	/**
	 * @var SlugField[]
	 */
	protected array $slugFields = array();

	function addSlugField(SlugField $slugField): void {
		$this->slugFields[$slugField->fieldName] = $slugField;
	}

	function getSlugFields(): array {
		return $this->slugFields;
	}

	function beforeCreate($data): array {
		return $data;
	}

	function afterCreate($id, $data): void {
		return;
	}

	function beforeDelete($data): void {
		return;
	}

	function afterDelete($data): void {
		return;
	}

	function beforeUpdate($id, $data): array {
		return $data;
	}

	function afterUpdate($id, $data): void {
		return;
	}

	function beforeClone($id, $data): array {
		return $data;
	}

	function afterClone($originalId, $newId, $data): void {
		return;
	}

	function isFormallyValidId($id): bool {
		return (is_numeric($id) or is_int($id));
	}

	function expandItem($id, array $data, $context = null): array {
		return $data;
	}

	function expandManyItems(array $items, $context = null): array {
		return array_map(function($item) use ($context) {
			return $this->expandItem($item['id'], $item, $context);
		}, $items);
	}


}
