<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use DateInterval;
use DateTime;
use Exception;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Give it [username] and [password] input parameters in body, it will validate them and create a token for you.
 */
class LoginController extends BaseAuthController {

	/**
	 * @var AuthenticatorInterface
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
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @param AuthenticatorInterface $authenticator
	 * @param DateInterval $tokenValidity
	 * @param string $usernameParamName
	 * @param string $passwordParamName
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		AuthenticatorInterface $authenticator,
		DateInterval $tokenValidity,
		string $usernameParamName = 'username',
		string $passwordParamName = 'password',
		LoggerInterface $logger = null
	) {
		$this->authenticator = $authenticator;
		$this->usernameParamName = $usernameParamName;
		$this->passwordParamName = $passwordParamName;
		$this->tokenValidity = $tokenValidity;
		$this->logger = $logger;
	}

	function __invoke(ServerRequestInterface $request, ResponseInterface $response) {
		return $this->run($request, $response);
	}

	function run(ServerRequestInterface $request, ResponseInterface $response) {

		$params = $request->getParsedBody();
		$username = $params[$this->usernameParamName] ?? '';
		$password = $params[$this->passwordParamName] ?? '';

		if (!$username or !$password) {
			throw new Exception('Missing username or password.', 400);
		}

		$identity = $this->authenticator->validateCredentials($username, $password);
		if (!$identity) {
			if ($this->logger) {
				$this->logger->info('Invalid login with username ' . $username . ' and password length ' . mb_strlen($password));
			}
			return $this->respondWith($response, array('success' => false, 'token' => '', 'id' => null, 'identity' => null));
		}

		$token = $this->authenticator->createToken($identity, new DateTime('now'));

		if ($token) {
			$this->authenticator->extendToken($token, $this->tokenValidity, new DateTime('now'));
		}

		if ($this->logger) {
			$this->logger->info('Successful login with username ' . $username . ' and password length ' . mb_strlen($password) . ', generated token ' . $token);
		}

		return $this->respondWith(
			$response,
			array('success' => true, 'token' => $token, 'id' => $identity->getId(), 'identity' => $identity->toArray())
		);

	}

}
