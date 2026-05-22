<?php

namespace OndraKoupil\AppTools\Slim;

use Psr\Log\LoggerInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Handlers\ErrorHandler as BaseErrorHandler;

class ErrorHandler404Aware extends BaseErrorHandler {

	protected LoggerInterface $loggerFor404Errors;

	protected bool $alsoFor405Errors = false;

	public function setLoggerFor404Errors(LoggerInterface $loggerFor404Errors, bool $alsoFor405Errors = true): void {
		$this->loggerFor404Errors = $loggerFor404Errors;
		$this->alsoFor405Errors = $alsoFor405Errors;
	}

	protected function logError(string $error): void {

		if ($this->loggerFor404Errors) {
			if (str_starts_with($error, '404 Not Found')) {
				$this->loggerFor404Errors->error('404 Not Found.');
			} elseif ($this->alsoFor405Errors and str_starts_with($error, '405 Method Not Allowed')) {
				$this->loggerFor404Errors->error('405 Method Not Allowed.');
			} else {
				parent::logError($error);
			}
		} else {
			parent::logError($error);
		}
	}

}
