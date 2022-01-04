<?php

namespace OndraKoupil\AppTools\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExtractTokenMiddleware implements MiddlewareInterface {

	const TOKEN = 'token';

	/**
	 * @var string
	 */
	private $tokenParamName;

	private $tokenRequestAttributeName;

	function __construct($tokenParamName = self::TOKEN, $tokenRequestAttributeName = self::TOKEN) {
		$this->tokenParamName = $tokenParamName;
		$this->tokenRequestAttributeName = $tokenRequestAttributeName;
	}

	function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		$params = $request->getParsedBody();
		$queryParams = $request->getQueryParams();
		$headers = $request->getHeaders();

		$token = $this->tokenParamName;

		$candidates = array(
			$queryParams[$token] ?? '',
			$params[$token] ?? '',
			$headers[$token] ?? '',
			$headers['Authorization'] ?? '',
			$headers['X-Authorization'] ?? '',
			$headers['X-' . $token] ?? '',
		);

		$tokenValue = '';
		foreach ($candidates as $candidate) {
			if ($candidate) {
				$tokenValue = $candidate;
			}
		}

		if (is_array($tokenValue)) {
			foreach ($tokenValue as $t) {
				if ($t) {
					$tokenValue = $t;
					break;
				}
			}
		}

		if ($tokenValue) {
			if (preg_match('~^' . preg_quote($this->tokenParamName). ':?\s?(.*)~i', $tokenValue, $matches)) {
				$tokenValue = $matches[1];
			}
			if (preg_match('~^bearer:?\s?(.*)~i', $tokenValue, $matches)) {
				$tokenValue = $matches[1];
			}
		}

		$requestWithToken = $request->withAttribute($this->tokenRequestAttributeName, $tokenValue);
		return $handler->handle($requestWithToken);
	}

}
