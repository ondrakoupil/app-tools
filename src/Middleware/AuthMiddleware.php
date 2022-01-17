<?php

namespace OndraKoupil\AppTools\Middleware;

use DateInterval;
use OndraKoupil\AppTools\Auth\Authenticator;
use OndraKoupil\AppTools\Auth\AuthenticatorInterface;
use OndraKoupil\AppTools\Auth\ValidationResult;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware that uses the token extracted from ExtractTokenMiddleware, validates it against Authenticator
 * and if check does not pass, returns 401.
 *
 * Adds attribute to the request with user's identity.
 */
class AuthMiddleware implements MiddlewareInterface {

	const USER = 'user';

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

	/**
	 * @var DateInterval
	 */
	private $tokenValidity;

	/**
	 * @var ResponseFactoryInterface
	 */
	private $responseFactory;

	/**
	 * @var callable|null
	 */
	private $responseBuilder;

	/**
	 * @param AuthenticatorInterface $authenticator
	 * @param ResponseFactoryInterface $responseFactory Used to create Response in case the access is not authenticated
	 * @param bool $authenticatedOnly True = Block unauthenticated access. False = allow.
	 * @param DateInterval|null $tokenValidity Automatically extend the token for this DateInterval on every successful access
	 * @param string $tokenAttrName Attr name for token attribute from ExtractTokenMiddleware
	 * @param string $userAttrName Attr name for Identity
	 * @param callable|null $responseBuilder Optional callback to further decorate or format the 401 response: $responseBuilder(ResponseInterface $response, string $message, int $authFailCode, ServerRequestInterface $request) return ResponseInterface
	 *    $authFailCode - viz ValidationResult
	 */
	function __construct(
		AuthenticatorInterface $authenticator,
		ResponseFactoryInterface $responseFactory,
		bool $authenticatedOnly = true,
		DateInterval $tokenValidity = null,
		string $tokenAttrName = ExtractTokenMiddleware::TOKEN,
		string $userAttrName = self::USER,
		callable $responseBuilder = null
	) {
		$this->authenticator = $authenticator;
		$this->authenticatedOnly = $authenticatedOnly;
		$this->tokenName = $tokenAttrName;
		$this->userAttrName = $userAttrName;
		$this->tokenValidity = $tokenValidity;
		$this->responseFactory = $responseFactory;
		$this->responseBuilder = $responseBuilder;

		if (!$this->tokenValidity) {
			$this->tokenValidity = new DateInterval('PT1H');
		}

	}

	function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		$token = $request->getAttribute($this->tokenName);
		if (!$token) {
			if ($this->authenticatedOnly) {
				return $this->respondWith(401, 'You need to authenticate yourself.', ValidationResult::REASON_UNKNOWN_TOKEN, $request);
			} else {
				return $handler->handle($request->withAttribute($this->userAttrName, null));
			}
		}

		$result = $this->authenticator->validateToken($token);

		if ($result->success) {

			$this->authenticator->extendToken($token, $this->tokenValidity);
			$request = $request->withAttribute($this->userAttrName, $result->identity);
			return $handler->handle($request);

		} else {

			if ($this->authenticatedOnly) {

				if ($result->reason === ValidationResult::REASON_EXPIRED_TOKEN) {
					return $this->respondWith(401, 'Token is not valid anymore, please login again.', $result->reason, $request);
				} else {
					if ($result->reason === ValidationResult::REASON_BLOCKED_USER) {
						return $this->respondWith(401, 'Your account is blocked.', $result->reason, $request);
					} else {
						return $this->respondWith(401, 'Token is not valid.', $result->reason, $request);
					}
				}

			} else {
				return $handler->handle($request->withAttribute($this->userAttrName, null));
			}

		}

	}

	protected function respondWith(int $code, string $message, int $authFailCode, RequestInterface $request): ResponseInterface {
		$response = $this->responseFactory->createResponse($code);
		if ($this->responseBuilder) {
			$builder = $this->responseBuilder;
			$response = $builder($response, $message, $authFailCode, $request);
		} else {
			$accepted = $request->getHeaderLine('Accept');
			$jsonPos = strpos($accepted, 'application/json');
			$textPos = strpos($accepted, 'text/');
			if ($jsonPos !== false and ($textPos === false or $textPos > $jsonPos)) {
				$response->getBody()->write(json_encode(array('error' => $message, 'errorCode' => $authFailCode ? ValidationResult::AUTH_FAIL_CODES[$authFailCode] : ''), JSON_THROW_ON_ERROR));
				$response = $response->withHeader('Content-Type', 'application/json');
			} else {
				$response->getBody()->write($message);
				$response = $response->withHeader('Content-Type', 'text/plain');
			}
		}
		return $response;
	}


}
