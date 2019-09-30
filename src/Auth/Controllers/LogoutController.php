<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use OndraKoupil\AppTools\Auth\Authenticator;
use Slim\Http\Request;
use Slim\Http\Response;

class LogoutController {

	/**
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * @var string
	 */
	protected $tokenParamName;

	/**
	 * @param Authenticator $authenticator
	 * @param string $tokenParamName
	 *
	 */
	public function __construct(Authenticator $authenticator, $tokenParamName = 'token') {
		$this->authenticator = $authenticator;
		$this->tokenParamName = $tokenParamName;
	}

	function __invoke(Request $request, Response $response, $args) {
		return $this->run($request, $response, $args);
	}

	function run(Request $request, Response $response, $args) {

		$token = $request->getParsedBodyParam($this->tokenParamName);

		if (!$token) {
			return $response->withStatus(400);
		}

		$this->authenticator->invalidateToken($token);
		return $response->withStatus(200);

	}

}
