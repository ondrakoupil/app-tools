<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Exception;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface as Authenticator;
use OndraKoupil\AppTools\Middleware\ExtractTokenMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Destroy the current token
 */
class LogoutController {

	/**
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * @var string
	 */
	protected $tokenAttributeName;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @param Authenticator $authenticator
	 * @param string $tokenAttributeName
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		Authenticator $authenticator,
		string $tokenAttributeName = ExtractTokenMiddleware::TOKEN,
		LoggerInterface $logger = null
	) {
		$this->authenticator = $authenticator;
		$this->tokenAttributeName = $tokenAttributeName;
		$this->logger = $logger;
	}

	function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
		return $this->run($request, $response);
	}

	function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

		$token = $request->getAttribute($this->tokenAttributeName);

		if (!$token) {
			throw new Exception('Missing token.', 400);
		}

		$this->authenticator->invalidateToken($token);
		
		if ($this->logger) {
			$this->logger->info('Logging out token ' . $token);
		}
		
		return $response;

	}

}
