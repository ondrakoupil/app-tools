<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

interface ActionInterface {

	/**
	 * @param string[] $files
	 *
	 * @return void
	 */
	public function cleanup(array $files): string;
	public function startup(): string;
	public function getActionId(): string;

	/**
	 * @param string $filePath
	 *
	 * @return string Lidsky čitelná zpráva vhodná do logu
	 */
	public function processFile(string $filePath): string;

}
