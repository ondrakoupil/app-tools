<?php

namespace OndraKoupil\AppTools\SimpleApi;

use Exception;
use Throwable;

class ItemNotFoundException extends Exception {

	/**
	 * @var string
	 */
	public $notFoundId = '';

	function __construct(string $notFoundId = '', string $message = '', int $code = 0, Throwable $previous = null) {
		$this->notFoundId = $notFoundId;
		if (!$message and $notFoundId) {
			$message = 'Item with this ID was not found: ' . $notFoundId;
		}
		parent::__construct($message, $code, $previous);
	}

}
