<?php

namespace OndraKoupil\AppTools\Slim;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class JsonErrorRenderer implements ErrorRendererInterface {

	public function __invoke(Throwable $exception, bool $displayErrorDetails): string {

		$payload = array(

		);

		if (!$displayErrorDetails) {
			$payload['error'] = 'An error occured.';
		} else {
			$payload['error'] = $exception->getMessage();
			$payload['errorDetails'] = array(
				'code' => $exception->getCode(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'type' => get_class($exception),
				'trace' => $exception->getTrace(),
			);
		}

		return json_encode($payload, JSON_PRETTY_PRINT);

	}

}
