<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use OndraKoupil\Tools\Files;

/**
 * Delete the files.
 */
class DeleteAction implements ActionInterface {

	public function cleanup(array $files): string {
		return '';
	}

	public function getActionId(): string {
		return 'delete';
	}

	public function processFile(string $filePath): string {
		Files::remove($filePath, true);
		return 'Deleted file';
	}

	public function startup(): string {
		return '';
	}


}
