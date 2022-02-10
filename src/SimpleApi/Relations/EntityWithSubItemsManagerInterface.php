<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface EntityWithSubItemsManagerInterface {

	function getSubItemsById(string $id, string $childEntityId = ''): array;

	function getSubItemsByIdExpanded(string $id, $expandParts = null, string $childEntityId = ''): array;

	function getManySubItemsById(array $ids, string $childEntityId = ''): array;

	function getManySubItemsByIdExpanded(array $ids, $expandParts = null, string $childEntityId = ''): array;

	/**
	 * Speed up getSubItemsById() and getManySubItemsById() methods
	 *
	 * @param string[] $ids
	 * @param string $childEntityId
	 *
	 * @return void
	 */
	function preloadManySubItems(array $ids, string $childEntityId = ''): void;

	/**
	 * Speed up getSubItemsById() and getManySubItemsById() methods
	 *
	 * @param string $childEntityId
	 *
	 * @return void
	 */
	function preloadAllSubItems(string $childEntityId = ''): void;

}
