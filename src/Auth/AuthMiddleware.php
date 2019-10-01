<?php

namespace OndraKoupil\AppTools\Auth;

use Slim\Http\Request;
use Slim\Http\Response;

class AuthMiddleware {

	/**
	 * @var Authenticator
	 */
	private $authenticator;

	/**
	 * @var bool
	 */
	private $authenticatedOnly;

	/**
	 * @var string
	 */
	private $tokenName;

	/**
	 * @var string
	 */
	private $userAttrName;

	function __construct(
		Authenticator $authenticator,
		bool $authenticatedOnly = true,
		string $tokenAttrName = 'token',
		string $userAttrName = 'user'
	) {
		$this->authenticator = $authenticator;
		$this->authenticatedOnly = $authenticatedOnly;
		$this->tokenName = $tokenAttrName;
		$this->userAttrName = $userAttrName;
	}

	function __invoke(Request $request, Response $response, $next) {

		$token = $request->getAttribute($this->tokenName);
		if (!$token) {

			if ($this->authenticatedOnly) {
				return $response->withJson(array('error' => 'You need to authenticate first.'), 401);
			} else {
				return $next($request, $response);
			}

		}

		$result = $this->authenticator->validateToken($token);

		if ($result->success) {
			$request = $request->withAttribute($this->userAttrName, $result->identity);
			return $next($request, $response);

		} else {

			if ($this->authenticatedOnly) {
				return $response->withJson(array('error' => 'Token is not valid.'), 403);
			} else {
				return $next($request, $response);
			}

		}

	}

}
