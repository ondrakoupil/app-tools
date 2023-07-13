<?php

namespace OndraKoupil\AppTools\SimpleApi\Controller;

use OndraKoupil\AppTools\Auth\IdentityInterface;
use OndraKoupil\AppTools\SimpleApi\Auth\EntityAuthorizatorInterface;
use OndraKoupil\AppTools\SimpleApi\Entity\Restriction;
use Psr\Http\Message\ServerRequestInterface;

interface FilterGetterInterface {

	function getFilterFromRequest(ServerRequestInterface $request, ?IdentityInterface $user, ?EntityAuthorizatorInterface $authorizator): ?Restriction;

}
