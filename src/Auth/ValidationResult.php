<?php

namespace OndraKoupil\AppTools\Auth;

class ValidationResult {

	const REASON_UNKNOWN_TOKEN = 1;
	const REASON_EXPIRED_TOKEN = 2;
	const REASON_BLOCKED_USER = 3;

	const AUTH_FAIL_CODES = array(
		self::REASON_UNKNOWN_TOKEN => 'UNKNOWN_TOKEN',
		self::REASON_EXPIRED_TOKEN => 'EXPIRED_TOKEN',
		self::REASON_BLOCKED_USER => 'BLOCKED_USER',
	);

	/**
	 * @var boolean
	 */
	public $success;

	/**
	 * @var int
	 */
	public $reason;

	/**
	 * @var IdentityInterface
	 */
	public $identity;

	/**
	 * @param bool $success
	 * @param int $reason
	 * @param IdentityInterface|null $identity
	 */
	public function __construct(bool $success, int $reason = 0, IdentityInterface $identity = null) {
		$this->success = $success;
		$this->reason = $reason;
		$this->identity = $identity;
	}


}
