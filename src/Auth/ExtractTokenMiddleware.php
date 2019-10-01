<?php

namespace OndraKoupil\AppTools\Auth;

use Slim\Http\Request;
use Slim\Http\Response;

class ExtractTokenMiddleware {

	/**
	 * @var string
	 */
	private $tokenParamName;

	function __construct($tokenParamName = 'token') {
		$this->tokenParamName = $tokenParamName;
	}

	function __invoke(Request $request, Response $response, $next) {

		$token = $request->getParam($this->tokenParamName);
		if (!$token) {
			$header1 = $request->getHeader($this->tokenParamName);
			$header2 = $request->getHeader('X-' . $this->tokenParamName);
			$header3 = $request->getHeader('Authorization');
			$header4 = $request->getHeader('X-Authorization');

			if ($header1) {
				$token = $header1;
			} elseif ($header2) {
				$token = $header2;
			} elseif ($header3) {
				$token = $header3;
			} elseif ($header4) {
				$token = $header4;
			}
		}

		if (is_array($token)) {
			foreach ($token as $t) {
				if ($t) {
					$token = $t;
					break;
				}
			}
		}

		if ($token) {
			if (preg_match('~^' . preg_quote($this->tokenParamName). ':?\s?(.*)~i', $token, $matches)) {
				$token = $matches[1];
			}
			if (preg_match('~^bearer:?\s?(.*)~i', $token, $matches)) {
				$token = $matches[1];
			}
		}

		$request = $request->withAttribute('token', $token);

		return $next($request, $response);
	}

}
