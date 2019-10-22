<?php

namespace OndraKoupil\AppTools;

use Exception;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class ErrorHandler {

	/**
	 * @var bool
	 */
	protected $displayErrorDetails;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	public function __construct($displayErrorDetails = false, LoggerInterface $logger = null) {

		$this->displayErrorDetails = $displayErrorDetails;
		$this->logger = $logger;

		if ($this->displayErrorDetails) {
			// Since we have error handler set up, no more displaying is needed
			error_reporting(0);
		}
	}

	public function __invoke(Request $request, Response $response, Exception $exception = null) {

		$status = $exception->getCode();
		if ($status >= 400 and $status < 600) {
			$response = $response->withStatus($status);
		} else {
			$response = $response->withStatus(500);
		}

		$payload = null;
		if ($this->displayErrorDetails) {
			$stack = array(
				'Exception code ' . ($exception->getCode() ?: '0')
				. ' in file ' . $exception->getFile()
				. ' at line ' . $exception->getLine()
			);
			$stack = array_merge($stack, explode("\n", $exception->getTraceAsString()));
			$payload = array(
				'error' => $exception->getMessage(),
				'errorStack' => $stack,
			);
		} else {

			$message = 'An error occurred';

			switch ($response->getStatusCode()) {
				case 400:
					$message = 'Some required parameters are missing.';
					break;

				case 401:
					$message = 'You need to authenticate first.';
					break;

				case 403:
					$message = 'You are not alowed to do this.';
					break;

				case 404:
					$message = 'Not found.';
					break;
			}

			$payload = array(
				'error' => $message,
			);
		}


		if ($this->logger) {
			$loggableMessage = $exception->getMessage();
			$maxCount = 10;
			$count = 0;
			if ($exception->getCode()) {
				$loggableMessage .= "\nCode " . $exception->getCode();
			}
			if ($exception->getFile()) {
				$loggableMessage .= "\nin " . $exception->getFile() . ' at line ' . $exception->getLine();
			}
			$stack = $exception->getTrace();
			foreach ($stack as $stackRow) {
				if ($count >= $maxCount) {
					break;
				}
				$count++;

				$loggableMessage .= "\n";
				$loggableMessage .= "#" . $count . ' ' . $stackRow['function'] . '() in ' . $stackRow['file'] . ' at line ' . $stackRow['line'];
			}
			if (count($stack) > $maxCount) {
				$loggableMessage .= "\n... truncated, stack size " . count($stack) . '';
			}
			$this->logger->error($loggableMessage);
		}



		$response = $response->withJson($payload);

		return $response;

	}

}
