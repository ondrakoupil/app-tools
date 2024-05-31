<?php

namespace OndraKoupil\AppTools;

use DateTime;
use Exception;
use Psr\Log\AbstractLogger;

/**
 * Logger do souboru
 */
class Logger extends AbstractLogger {

	protected $filePath;

	protected $fileHandle = null;

	protected $runId;

	protected $logCallback;

	protected $logHeaderCallback;

	protected $logExceptionCallback;

	function __construct($logName, $logDirectory, $runId = null, DateTime $today = null) {
		if (!$today) {
			$today = new DateTime('now');
		}
		$this->filePath = $logDirectory . '/' . $logName . '.' . $today->format('Y-m-d') . '.log';
		$this->runId = $runId;
	}

	function setLogCallback(callable $callback): void {
		$this->logCallback = $callback;
	}

	function setLogHeaderCallback(callable $callback): void {
		$this->logHeaderCallback = $callback;
	}

	public function setLogExceptionCallback(callable $logExceptionCallback): void {
		$this->logExceptionCallback = $logExceptionCallback;
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
			$processedException = false;
			if ($this->logExceptionCallback) {
				$res = call_user_func_array($this->logExceptionCallback, array($message, $level, $context));
				if ($res and is_string($res)) {
					$processedException = true;
					$message = $res;
				}
			}
			if (!$processedException) {
				$message = get_class($message) . " at " . $message->getFile() . " line " . $message->getLine() . "\n" . $message->getMessage() . "\n" . $message->getTraceAsString();
			}
		}
		if ($this->logCallback) {
			$messageReturned = call_user_func_array($this->logCallback, array($message, $level, $context));
			if ($messageReturned and is_string($messageReturned)) {
				$message = $messageReturned;
			}
		}
		if ($this->logHeaderCallback) {
			$header = call_user_func_array($this->logHeaderCallback, array($message, $level, $context));
			if ($header and is_string($header)) {
				$message = $header . "\n" . $message;
			}
		}
		$write = date('Y-m-d H:i:s') . ' - ' . ($this->runId ? ($this->runId . ' - ') : '') . $level . ' - ' . $message;
		fwrite($this->fileHandle, $write . "\n\n");
	}

}
