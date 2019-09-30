<?php

namespace OndraKoupil\AppTools\Auth;

class ValidationResult {

	const REASON_UNKNOWN_TOKEN = 1;
	const REASON_EXPIRED_TOKEN = 2;
	const REASON_BLOCKED_USER = 3;

	/**
	 * @var boolean
	 */
	public $success;

	/**
	 * @var int
	 */
	public $reason;

	/**
	 * @var Identity
	 */
	public $identity;

	/**
	 * @param bool $success
	 * @param int $reason
	 * @param Identity $identity
	 */
	public function __construct(bool $success, int $reason = 0, Identity $identity = null) {
		$this->success = $success;
		$this->reason = $reason;
		$this->identity = $identity;
	}


}
