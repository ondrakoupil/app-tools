<?php

namespace OndraKoupil\AppTools\SimpleApi\Relations;

interface EntityWithParentItemManagerInterface {

	function getParentById(string $id, string $parentEntityId = ''): string;

	function getParentByIdExpanded(string $id, $expandParts = null, string $parentEntityId = ''): array;

	function getManyParentsById(array $id, string $parentEntityId = ''): array;

	function getManyParentsByIdExpanded(array $id, $expandParts = null, string $parentEntityId = ''): array;

	/**
	 * Speed up using getParentById() and getManyParentsById()
	 *
	 * @param string[] $ids
	 * @param string $parentEntityId
	 *
	 * @return void
	 */
	function preloadManyParents(array $ids, string $parentEntityId = ''): void;

	/**
	 * Speed up using getParentById() and getManyParentsById()
	 *
	 * @param string $parentEntityId
	 *
	 * @return void
	 */
	function preloadAllParents(string $parentEntityId = ''): void;

}
