<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use OndraKoupil\Tools\Exceptions\FileAccessException;
use OndraKoupil\Tools\Files;

/**
 * Move files to another directory and optionally delete them only after longer period of time.
 */
class ArchiveAction implements ActionInterface {

	/**
	 * @var string
	 */
	protected $archiveDirectory;

	/**
	 * @var int
	 */
	protected $trashAfterDays;

	/**
	 * @var string
	 */
	protected $hash;

	/**
	 * @var ActionInterface|null
	 */
	protected $actionAfterTrash;

	function __construct(string $archiveDirectory, int $trashAfterDays = 0, ActionInterface $actionAfterTrash = null) {
		$this->archiveDirectory = $archiveDirectory;
		$this->trashAfterDays = $trashAfterDays;
		$this->hash = substr(md5($this->archiveDirectory . time() . rand(10000,99999)), 0, 8);
		$this->actionAfterTrash = $actionAfterTrash;
	}

	public function getActionId(): string {
		return 'archive';
	}

	public function processFile(string $filePath): string {
		$ext = Files::extension($filePath);
		$newFileName = Files::safeName($filePath);
		$target = $this->archiveDirectory . '/' . $newFileName . '.' . $this->hash . '.' . $ext;
		$ok = rename($filePath, $target);
		if (!$ok) {
			throw new FileAccessException('Can not move to ' . $target);
		}
		return 'Archived to ' . $target;
	}

	public function cleanup(array $files): string {
		$now = time();
		$log = array();
		if ($this->trashAfterDays and $this->actionAfterTrash) {
			$allFilesInTrash = FilePurger::findAllFilesInDirectoryRecursive($this->archiveDirectory);
			foreach ($allFilesInTrash as $file) {
				$fileTime = filemtime($file);
				if ($now - $fileTime > $this->trashAfterDays * 86400) {
					$log[] = $this->actionAfterTrash->processFile($file);
				}
			}
		}
		return implode("\n", $log);
	}

	public function startup(): string {
		return '';
	}


}
