<?php

namespace OndraKoupil\AppTools\Slim;

use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as BaseErrorHandler;

class ErrorHandler404Aware extends BaseErrorHandler {

	protected LoggerInterface $loggerFor404Errors;

	public function setLoggerFor404Errors(LoggerInterface $loggerFor404Errors): void {
		$this->loggerFor404Errors = $loggerFor404Errors;
	}

	protected function logError(string $error): void {

		if ($this->loggerFor404Errors) {
			if (!str_starts_with($error, '404 Not Found')) {
				parent::logError($error);
			} else {
				$this->loggerFor404Errors->error('404 Not Found.');
			}
		} else {
			parent::logError($error);
		}
	}

}
