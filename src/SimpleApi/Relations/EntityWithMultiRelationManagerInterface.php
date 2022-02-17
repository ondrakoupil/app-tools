<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface EntityWithMultiRelationManagerInterface {

	function getRelatedItemsById(string $id, string $otherEntityId = ''): array;

	function getRelatedItemsByIdExpanded(string $id, $expandParts = null, string $otherEntityId = ''): array;

	function getManyRelatedItemsById(array $ids, string $otherEntityId = ''): array;

	function getManyRelatedItemsByIdExpanded(array $ids, $expandParts = null, string $otherEntityId = ''): array;

	function preloadManyRelatedItems(array $ids, string $otherEntityId = ''): void;

	function preloadAllRelatedItems(string $otherEntityId = ''): void;

}
