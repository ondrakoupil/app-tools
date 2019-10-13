<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use DateInterval;
use DateTime;
use Exception;
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
	 * @var DateInterval
	 */
	private $tokenValidity;

	/**
	 * @param Authenticator $authenticator
	 * @param DateInterval $tokenValidity
	 * @param string $usernameParamName
	 * @param string $passwordParamName
	 */
	public function __construct(Authenticator $authenticator, DateInterval $tokenValidity, $usernameParamName = 'username', $passwordParamName = 'password') {
		$this->authenticator = $authenticator;
		$this->usernameParamName = $usernameParamName;
		$this->passwordParamName = $passwordParamName;
		$this->tokenValidity = $tokenValidity;
	}

	function __invoke(Request $request, Response $response, $args) {
		return $this->run($request, $response, $args);
	}

	function run(Request $request, Response $response, $args) {

		$username = $request->getParam($this->usernameParamName);
		$password = $request->getParam($this->passwordParamName);

		if (!$username or !$password) {
			throw new Exception('Missing username or password.', 400);
		}

		$identity = $this->authenticator->validateCredentials($username, $password);
		if (!$identity) {
			return $response->withJson(array('success' => false, 'token' => '', 'id' => null, 'identity' => null));
		}

		$token = $this->authenticator->createToken($identity, new DateTime('now'));

		if ($token) {
			$this->authenticator->extendToken($token, $this->tokenValidity, new DateTime('now'));
		}

		return $response->withJson(
			array('success' => true, 'token' => $token, 'id' => $identity->getId(), 'identity' => $identity->toArray())
		);

	}

}
