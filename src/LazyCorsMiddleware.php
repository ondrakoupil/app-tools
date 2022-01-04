<?php

namespace OndraKoupil\AppTools;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds semi-inteligent CORS capabilities.
 *
 *
 * Usage in Slim 4:
 *
 * ```
 * $app->add(
 *     new LazyCorsMiddleware(
 *         $app->getResponseFactory(),
 *         function($request) {
 *             $routeContext = RouteContext::fromRequest($request);
 *             $routingResults = $routeContext->getRoutingResults();
 *             return $routingResults->getAllowedMethods();
 *         }
 *     )
 * );
 *
 * // Must be added AFTER LazyCorsMiddleware
 * $app->addRoutingMiddleware();
 *
 * // Add a route for all OPTIONS requests that justs returns the response.
 * // It is here just for Slim's router to allow OPTIONS requests. The body is not reached since it is short-circuited in the middleware.
 * $app->options('/{routes:.+}', function ($request, $response, $args) {
 *   return $response;
 * });
 *
 * ```
 *
 * @see https://github.com/slimphp/Slim/issues/2667
 * @see https://www.slimframework.com/docs/v4/cookbook/enable-cors.html
 *
 */
class LazyCorsMiddleware {

	/**
	 * @var ResponseFactoryInterface
	 */
	private $responseFactory;

	/**
	 * @var callable
	 */
	private $availableMethodsGetter;

	const DEFAULT_ALLOWED_METHODS = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS');

	function __construct(ResponseFactoryInterface $responseFactory, callable $availableMethodsGetter = null) {
		$this->responseFactory = $responseFactory;
		$this->availableMethodsGetter = $availableMethodsGetter;
	}

	function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

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

		$allowedMethods = $this->evalAllowedMethods($request);

		if ($request->getMethod() === 'OPTIONS') {
			// Short-circuit
			$response = $this->responseFactory->createResponse(204);
			return $this->addHeadersToResponse($response, $allowOrigin, $allowedMethods);
		}

		$response = $handler->handle($request);
		return $this->addHeadersToResponse($response, $allowOrigin, $allowedMethods);

	}

	protected function evalAllowedMethods(ServerRequestInterface $request) {
		$availableMethodsGetter = $this->availableMethodsGetter;
		if (!$availableMethodsGetter) {
			return self::DEFAULT_ALLOWED_METHODS;
		} else {
			$allowedMethods = $availableMethodsGetter($request);
			if (!$allowedMethods and !is_array($allowedMethods)) {
				return self::DEFAULT_ALLOWED_METHODS;
			}
			return $allowedMethods;
		}
	}

	protected function addHeadersToResponse(ResponseInterface $response, string $allowOriginHeaderValue, array $allowedMethods): ResponseInterface {
		return $response
			->withHeader('Access-Control-Allow-Origin', $allowOriginHeaderValue)
			->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
			->withHeader('Access-Control-Allow-Methods', implode(',', $allowedMethods))
		;
	}

}
