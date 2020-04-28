<?php


namespace OndraKoupil\AppTools\Importing\Importer;

use OndraKoupil\AppTools\Importing\Reader\ReaderInterface;

interface ImporterInterface {

	/**
	 * @param ReaderInterface $reader
	 * @return void
	 */
	function setReader(ReaderInterface $reader);

	/**
	 * @param callable $callback
	 * @return void
	 */
	function setTransformCallback(callable $callback);

	/**
	 * @param callable $callback
	 * @return void
	 */
	function setAfterSaveCallback(callable $callback);

	/**
	 * @return void
	 */
	function import();

	/**
	 * @return int
	 */
	function getImportedItemsCount();



}
