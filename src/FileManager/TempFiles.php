<?php

namespace OndraKoupil\AppTools\FileManager;

use OndraKoupil\AppTools\FileManager\Purger\DeleteAction;
use OndraKoupil\AppTools\FileManager\Purger\FilePurger;
use OndraKoupil\Tools\Strings;
use Psr\Log\LoggerInterface;

class TempFiles {

	public function __construct(
		protected string $tempDir,
		protected int $ttlHours = 24,
		protected ?LoggerInterface $purgerLogger = null
	) {
		if (substr($this->tempDir, -1) === '/') {
			$this->tempDir = substr($this->tempDir, 0, -1);
		}
	}

	function suggestTempFile() {
		$rand = Strings::randomString(8, true);
		return $this->tempDir . '/' . $rand;
	}

	function purge() {
		$purger = new FilePurger($this->tempDir, new DeleteAction(), $this->purgerLogger);
		$purger->setMinAgeInHours($this->ttlHours);
		$purger->run();
	}

}
