<?php

namespace OndraKoupil\AppTools\Middleware;

use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Allow accessing this route only from valid IPs
 *
 * Requires request attribute "ip_address" from akrabat/ip-address-middleware
 */
class IpWhitelistMiddleware implements MiddlewareInterface {

	/**
	 * @var ResponseFactoryInterface
	 */
	private $responseFactory;

	/**
	 * @var string[]
	 */
	private $ipWhitelist;

	/**
	 * @param string[] $ipWhitelist
	 * @param ResponseFactoryInterface $responseFactory
	 */
	function __construct(
		array $ipWhitelist,
		ResponseFactoryInterface $responseFactory
	) {
		$this->responseFactory = $responseFactory;
		$this->ipWhitelist = $ipWhitelist;
	}

	function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		$ip = $request->getAttribute('ip_address');
		if (!$ip) {
			throw new Exception('No "ip_address" attribute was present in the request');
		}

		$isWhitelisted = in_array($ip, $this->ipWhitelist);

		if (!$isWhitelisted) {
			$resp = $this->responseFactory->createResponse(403);
			$resp->getBody()->write('This can be called only from whitelisted IP addresses.');
			return $resp;
		}

		return $handler->handle($request);


	}

}
