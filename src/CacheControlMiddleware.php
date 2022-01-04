<?php

namespace OndraKoupil\AppTools;

use DateInterval;
use DateTime;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class CacheControlMiddleware implements MiddlewareInterface {

	/**
	 * @var int
	 */
	private $maxCacheLengthInSeconds;

	function __construct(int $maxCacheLengthInSeconds = 0) {
		$this->maxCacheLengthInSeconds = $maxCacheLengthInSeconds;
	}

	function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		$response = $handler->handle($request);

		if ($request->getMethod() === 'OPTIONS') {
			return $response;
		}

		if (!$this->maxCacheLengthInSeconds) {
			return $response
				->withHeader('Cache-control', 'no-cache, no-store, must-revalidate')
				->withHeader('Pragma', 'no-cache')
				->withHeader('Expires', '0')
			;
		} else {
			$expireTime = new DateTime('now');
			$expireTime = $expireTime->add(new DateInterval('PT' . $this->maxCacheLengthInSeconds . 'S'));
			return $response
				->withHeader('Cache-control', 'max-age=' . $this->maxCacheLengthInSeconds)
				->withHeader('Pragma', 'public')
				->withHeader('Expires', $expireTime->format('r'))
			;
		}

	}

}
