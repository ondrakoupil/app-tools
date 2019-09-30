<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use OndraKoupil\AppTools\Auth\Authenticator;
use Slim\Http\Request;
use Slim\Http\Response;

class LoginController {

	/**
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * @var string
	 */
	protected $usernameParamName;

	/**
	 * @var string
	 */
	protected $passwordParamName;

	/**
	 * @param Authenticator $authenticator
	 * @param string $usernameParamName
	 * @param string $passwordParamName
	 */
	public function __construct(Authenticator $authenticator, $usernameParamName = 'username', $passwordParamName = 'password') {
		$this->authenticator = $authenticator;
		$this->usernameParamName = $usernameParamName;
		$this->passwordParamName = $passwordParamName;
	}

	function __invoke(Request $request, Response $response, $args) {
		return $this->run($request, $response, $args);
	}

	function run(Request $request, Response $response, $args) {

		$username = $request->getParsedBodyParam($this->usernameParamName);
		$password = $request->getParsedBodyParam($this->passwordParamName);

		if (!$username or !$password) {
			return $response->withStatus(400);
		}

		$identity = $this->authenticator->validateCredentials($username, $password);
		if (!$identity) {
			return $response->withStatus(401);
		}

		$token = $this->authenticator->createToken($identity);
		return $response->withJson(
			array('token' => $token, 'id' => $identity->getId(), 'identity' => $identity->toArray())
		);

	}

}
