<?php

namespace OndraKoupil\AppTools\SimpleApi\Auth;

use OndraKoupil\AppTools\Auth\IdentityWithRolesInterface;
use OndraKoupil\AppTools\Middleware\AuthMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class DefaultIdentityExtractor implements IdentityExtractorInterface {

	function getUser(ServerRequestInterface $request): ?IdentityWithRolesInterface {
		return $request->getAttribute(AuthMiddleware::USER) ?: null;
	}


}
