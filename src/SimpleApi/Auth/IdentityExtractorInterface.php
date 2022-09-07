<?php

namespace OndraKoupil\AppTools\SimpleApi\Auth;

use OndraKoupil\AppTools\Auth\IdentityWithRolesInterface;
use Psr\Http\Message\ServerRequestInterface;

interface IdentityExtractorInterface {

	function getUser(ServerRequestInterface $request): ?IdentityWithRolesInterface;

}
