<?php

namespace OndraKoupil\AppTools\SimpleApi\Auth;

use OndraKoupil\AppTools\Auth\IdentityWithRolesInterface;

interface EntityAuthorizatorInterface {

	function canView(IdentityWithRolesInterface $user, $item): bool;

	function canViewMany(IdentityWithRolesInterface $user, array $items): bool;

	function createListRestriction(IdentityWithRolesInterface $user);

	/* More supported methods are about to come */

}
