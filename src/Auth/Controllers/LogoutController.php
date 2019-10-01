<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Exception;
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

		$token = $request->getParam($this->tokenParamName);

		if (!$token) {
			$token = $request->getAttribute($this->tokenParamName);
		}

		if (!$token) {
			throw new Exception('Missing token.', 400);
		}

		$this->authenticator->invalidateToken($token);
		return $response;

	}

}
