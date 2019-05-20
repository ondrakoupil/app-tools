<?php

namespace Zelen;

use DateTime;
use Exception;
use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

	protected $filePath;

	protected $fileHandle = null;

	protected $runId;

	function __construct($logName, $logDirectory, $runId = null, DateTime $today = null) {
		if (!$today) {
			$today = new DateTime('now');
		}
		$this->filePath = $logDirectory . '/' . $logName . '.' . $today->format('Y-m-d') . '.log';
		$this->runId = $runId;
	}

	function openIfNeeded() {
		if (!$this->fileHandle) {
			$this->fileHandle = fopen(
				$this->filePath,
				'a'
			);
		}
	}

	public function log($level, $message, array $context = array()) {
		$this->openIfNeeded();
		if ($message instanceof Exception) {
			$message = get_class($message) . " at " . $message->getFile() . " line " . $message->getLine() . "\n" . $message->getMessage() . "\n" . $message->getTraceAsString();
		}
		$write = date('Y-m-d H:i:s') . ' - ' . ($this->runId ? ($this->runId . ' - ') : '') . $level . ' - ' . $message;
		fwrite($this->fileHandle, $write . "\n\n");
	}

}
