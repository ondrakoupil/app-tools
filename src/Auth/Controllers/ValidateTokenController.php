<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use DateInterval;
use Exception;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface as Authenticator;
use OndraKoupil\AppTools\Middleware\ExtractTokenMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Validate given token and return information about yourself
 */
class ValidateTokenController extends BaseAuthController {

	/**
	 * @var Authenticator
	 */
	protected $authenticator;

	/**
	 * @var string
	 */
	private $tokenAttributeName;

	/**
	 * @var DateInterval
	 */
	private $tokenValidity;

	/**
	 * @var LoggerInterface|null
	 */
	private $logger;

	/**
	 * @param Authenticator $authenticator
	 * @param DateInterval $tokenValidity
	 * @param string $tokenAttributeName
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		Authenticator $authenticator,
		DateInterval $tokenValidity,
		string $tokenAttributeName = ExtractTokenMiddleware::TOKEN,
		LoggerInterface $logger = null
	) {
		$this->authenticator = $authenticator;
		$this->tokenAttributeName = $tokenAttributeName;
		$this->tokenValidity = $tokenValidity;
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

		$result = $this->authenticator->validateToken($token);

		if ($result->success) {
			$this->authenticator->extendToken($token, $this->tokenValidity);
			$identity = $result->identity->toArray();
			$id = $result->identity->getId();
			if ($this->logger) {
				$this->logger->info('Successfully verifying token ' . $token . ' of user ' . $id);
			}
			return $this->respondWith($response, array('id' => $id, 'identity' => $identity));
		}

		if ($this->logger) {
			$this->logger->info('Unsuccessfully verifying token ' . $token);
		}
		return $this->respondWith($response, array('id' => null, 'identity' => null, 'reason' => $result->reason));


	}

}
