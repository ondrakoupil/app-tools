<?php

namespace OndraKoupil\AppTools\SimpleApi;

use Exception;
use Throwable;

class ItemNotFoundException extends Exception {

	public string $notFoundId = '';

	function __construct(string $notFoundId = '', string $message = '', int $code = 0, Throwable $previous = null) {
		$this->notFoundId = $notFoundId;
		parent::__construct($message, $code, $previous);
	}

}
