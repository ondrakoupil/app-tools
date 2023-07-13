<?php

namespace OndraKoupil\AppTools\SimpleApi\Auth;

use OndraKoupil\AppTools\Auth\IdentityWithRolesInterface;
use OndraKoupil\AppTools\SimpleApi\Entity\Restriction;

interface EntityAuthorizatorInterface {

	function canView(IdentityWithRolesInterface $user, $item): bool;

	function canViewMany(IdentityWithRolesInterface $user, array $items): bool;

	function canDelete(IdentityWithRolesInterface $user, $item): bool;

	function createListRestriction(IdentityWithRolesInterface $user): ?Restriction;

	function canEdit(IdentityWithRolesInterface $user, $item, $itemChanges): bool;

	/* More supported methods are about to come */

}
