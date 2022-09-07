<?php

namespace OndraKoupil\AppTools\Auth;

class SimpleRole implements UserRoleInterface {

	/**
	 * @var string
	 */
	private $roleId;

	function __construct(string $roleId) {

		$this->roleId = $roleId;
	}

	function getRoleId(): string {
		return $this->roleId;
	}


}
