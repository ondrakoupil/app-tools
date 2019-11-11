<?php

namespace OndraKoupil\AppTools;

use DateInterval;
use DateTime;
use Slim\Http\Request;
use Slim\Http\Response;

class CacheControlMiddleware {

	/**
	 * @var int
	 */
	private $maxCacheLengthInSeconds;

	function __construct($maxCacheLengthInSeconds = 0) {
		$this->maxCacheLengthInSeconds = $maxCacheLengthInSeconds;
	}

	function __invoke(Request $request, Response $response, callable $next) {

		if ($request->getMethod() === 'OPTIONS') {
			return $next($request, $response);
		}

		/** @var Response $nextResponse */
		$nextResponse = $next($request, $response);

		if (!$this->maxCacheLengthInSeconds) {
			$newNextResponse =
				$nextResponse
					->withHeader('Cache-control', 'no-cache, no-store, must-revalidate')
					->withHeader('Pragma', 'no-cache')
					->withHeader('Expires', '0')
			;
		} else {
			$expireTime = new DateTime('now');
			$expireTime = $expireTime->add(new DateInterval('PT' . $this->maxCacheLengthInSeconds . 's'));
			$newNextResponse =
				$nextResponse
					->withHeader('Cache-control', 'max-age=' . $this->maxCacheLengthInSeconds)
					->withHeader('Pragma', 'public')
					->withHeader('Expires', $expireTime->format('r'))
			;
		}

		return $newNextResponse;

	}

}
