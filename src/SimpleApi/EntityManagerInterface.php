<?php

namespace OndraKoupil\AppTools\SimpleApi;

interface EntityManagerInterface {

	/**
	 * @return string[]
	 */
	function getAllIds(): array;

	/**
	 * @param mixed $context
	 *
	 * @return array
	 */
	function getAllItems($context = null): array;

	function createItem(array $data): array;

	/**
	 * @throws ItemNotFoundException
	 */
	function deleteItem(string $id): void;

	/**
	 * @param string[] $id
	 * @throws ItemNotFoundException
	 * @return void
	 */
	function deleteManyItems(array $id): void;

	/**
	 * @throws ItemNotFoundException
	 */
	function updateItem(string $id, array $data): void;

	/**
	 * @throws ItemNotFoundException
	 */
	function cloneItem(string $id): array;

	/**
	 * @param string $id
	 * @param mixed $context
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	function getItem(string $id, $context = null): array;

	/**
	 * @param string[] $ids
	 * @param mixed $context
	 *
	 * @return array
	 * @throws ItemNotFoundException
	 */
	function getManyItems(array $ids, $context = null): array;

	/**
)	 * @param string $id
	 *
	 * @return bool
	 */
	function exists(string $id): bool;

	/**
	 * @param string[] $ids
	 *
	 * @return bool
	 */
	function existsAllOf(array $ids): bool;

}
