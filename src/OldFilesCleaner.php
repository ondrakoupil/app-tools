<?php

namespace OndraKoupil\AppTools;

use DateTime;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class OldFilesCleaner {

	protected $rules = array();

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @var int
	 */
	private $now;

	function __construct(LoggerInterface $logger = null, DateTime $now = null) {
		$this->logger = $logger;

		if (!$now) {
			$now = new DateTime('now');
		}
		$this->now = $now->getTimestamp();
	}
	
	function addRule(string $glob, int $keepHours): void {
		$this->rules[] = array(
			'glob' => $glob,
			'hours' => $keepHours,
		);
	}
	
	function run() {
		foreach ($this->rules as $rule) {
			$files = glob($rule['glob']);
			$limit = $this->now - $rule['hours'] * 3600;
			if ($files) {
				foreach ($files as $file) {
					$mtime = filemtime($file);
					if ($mtime < $limit) {
						$ok = unlink($file);
						if ($ok) {
							$this->log('Deleted ' . $file);
						} else {
							$this->log('Could not delete ' . $file, LogLevel::ERROR);
						}
					}
				}
			}
		}
	}

	protected function log($message, $level = LogLevel::INFO): void {
		if ($this->logger) {
			$this->logger->log($level, $message);
		}
	}
	
	
}
