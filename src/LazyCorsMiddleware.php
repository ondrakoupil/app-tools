<?php

namespace OndraKoupil\AppTools;

use Slim\Http\Request;
use Slim\Http\Response;

class LazyCorsMiddleware {

	protected $allowedMethods = array();

	function __construct($allowedMethods = null) {
		if (!$allowedMethods) {
			$allowedMethods = array('GET', 'POST');
		}
		$this->allowedMethods = $allowedMethods;
	}

	function __invoke(Request $request, Response $response, callable $next) {

		$originHeader = $request->getHeader('Origin');
		if ($originHeader) {
			$originHeader = $originHeader[0];
		} else {
			$originHeader = '';
		}
		$allowOrigin = '*';
		if (preg_match('~localhost~i', $originHeader)) {
			$allowOrigin = $originHeader;
		}

		if ($request->getMethod() === 'OPTIONS') {
			$newResponse =
				$response
					->withHeader('Access-Control-Allow-Origin', $allowOrigin)
					->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
					->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));

			return $newResponse;
		}

		/** @var Response $nextResponse */
		$nextResponse = $next($request, $response);

		$newNextResponse =
			$nextResponse
				->withHeader('Access-Control-Allow-Origin', $allowOrigin)
				->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
				->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));

		return $newNextResponse;

	}

}
