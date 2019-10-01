<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use DateInterval;
use Exception;
use OndraKoupil\AppTools\Auth\Authenticator;
use Slim\Http\Request;
use Slim\Http\Response;

class ValidateTokenController {

	/**
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * @var string
	 */
	private $tokenParamName;

	/**
	 * @var DateInterval
	 */
	private $tokenValidity;

	/**
	 * @param Authenticator $authenticator
	 * @param DateInterval $tokenValidity
	 * @param string $tokenParamName
	 *
	 * @throws Exception Invalid token validity value
	 */
	public function __construct(Authenticator $authenticator, DateInterval $tokenValidity, $tokenParamName = 'token') {
		$this->authenticator = $authenticator;
		$this->tokenParamName = $tokenParamName;
		$this->tokenValidity = $tokenValidity;
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

		$result = $this->authenticator->validateToken($token);

		if ($result->success) {
			$this->authenticator->extendToken($token, $this->tokenValidity);
			$identity = $result->identity->toArray();
			$id = $result->identity->getId();
			return $response->withJson(array('id' => $id, 'identity' => $identity));
		}

		if (!$result->success) {
			return $response->withJson(array('id' => null, 'identity' => null, 'reason' => $result->reason));
		}


	}

}
