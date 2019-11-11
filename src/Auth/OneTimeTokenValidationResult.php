<?php

namespace OndraKoupil\AppTools\Auth;

use DateTime;

class OneTimeTokenValidationResult {

	/**
	 * @var mixed
	 */
	public $userId;

	/**
	 * @var DateTime
	 */
	public $validUntil;

	/**
	 * @var mixed
	 */
	public $data;

	/**
	 * @var boolean
	 */
	public $valid;

	/**
	 * @param bool $valid
	 * @param mixed $userId
	 * @param DateTime $validUntil
	 * @param mixed $data
	 */
	public function __construct($valid, $userId = null, DateTime $validUntil = null, $data = null) {
		$this->userId = $userId;
		$this->validUntil = $validUntil;
		$this->data = $data;
		$this->valid = !!$valid;
	}


}
