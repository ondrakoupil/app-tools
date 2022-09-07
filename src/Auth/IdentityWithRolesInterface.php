<?php

namespace OndraKoupil\AppTools\Auth;

interface IdentityWithRolesInterface extends IdentityInterface {

	/**
	 * @return UserRoleInterface[]
	 */
	public function getAllRoles(): array;

	public function hasRole(UserRoleInterface $role): bool;

}
