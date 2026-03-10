<?php

namespace OndraKoupil\AppTools;

use DateTime;
use Exception;
use Psr\Log\AbstractLogger;

/**
 * Logger do souboru
 */
class LoggerWithServerData extends Logger {

	protected $serverData;

	function __construct($logName, $logDirectory, array $serverData, $runId = null, DateTime $today = null) {
		parent::__construct($logName, $logDirectory, $runId, $today);
		$this->setServerData($serverData);
		$this->logHeaderCallback = array($this, 'defaultLogHeaderCallback');
	}

	/**
	 * @param array $serverData
	 */
	public function setServerData($serverData): void {
		$this->serverData = $serverData;
	}

	public function defaultLogHeaderCallback($message) {
		if (!$this->serverData) {
			return $message;
		}
		return 'REQ: ' . $this->serverData['REQUEST_METHOD']
			. ' ' . $this->serverData['REQUEST_URI']
			. "\n"
			. 'IP: ' . ($this->serverData['HTTP_X_CLIENT_IP'] ?? $this->serverData['REMOTE_ADDR'] ?? '???')
			. ' - UA: ' . $this->serverData['HTTP_USER_AGENT']
			;
	}



}
