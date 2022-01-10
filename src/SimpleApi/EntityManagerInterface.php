<?php

namespace OndraKoupil\AppTools\SimpleApi;

interface EntityManagerInterface {

	function getAllItems(): array;
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

}
