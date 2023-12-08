<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use OndraKoupil\Tools\Exceptions\FileAccessException;
use OndraKoupil\Tools\Files;

/**
 * Don't to anything at all.
 */
class DryRunAction implements ActionInterface {

	public function cleanup(array $files): string {
		return '';
	}

	public function startup(): string {
		return '';
	}

	public function getActionId(): string {
		return 'noop';
	}

	public function processFile(string $filePath): string {
		return 'Dry run';
	}


}
