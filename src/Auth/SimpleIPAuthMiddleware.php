<?php

namespace OndraKoupil\AppTools\Auth;

use Slim\Http\Request;
use Slim\Http\Response;
use RuntimeException;

class SimpleIPAuthMiddleware {

	protected $allowedIPs;

	protected $alsoAllowCallback = null;

	/**
	 *
	 *
	 * @param string[] $allowedIPs Array of allowed IPs
	 * @param callable $alsoAllowCallback Optional: function ($request, $response) => bool;  if returns true, middleware will consider the request to be allowed.
	 */
	function __construct(
		$allowedIPs,
		$alsoAllowCallback = null
	) {
		$this->allowedIPs = $allowedIPs;
		$this->alsoAllowCallback = $alsoAllowCallback;
	}

	function __invoke(Request $request, Response $response, $next) {

		$ip = $request->getAttribute('ip_address');

		if (!$ip) {
			throw new RuntimeException('No IP address is in \"ip_address\" request attribute. You probably forgot to use ip-address-middleware.');
		}

		if (in_array($ip, $this->allowedIPs)) {
			return $next($request, $response);
		} else {

			if ($this->alsoAllowCallback) {
				$alsoAllow = call_user_func_array($this->alsoAllowCallback, array($request, $response));
				if ($alsoAllow === true) {
					return $next($request, $response);
				}
			}
			return $response->withJson(array('error' => 'This endpoint can be called only from specified IP addresses.'), 403);
		}


	}

}
