<?php

namespace OndraKoupil\AppTools\SimpleApi;

use OndraKoupil\Tools\Arrays;
use Psr\Http\Server\MiddlewareInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class RouteCreator {

	/**
	 *
	 *
	 * @param App $app
	 * @param string $entityName
	 * @param string $controllerClassNameOrToken
	 * @param MiddlewareInterface|MiddlewareInterface[] $middlewareForReading
	 * @param MiddlewareInterface|MiddlewareInterface[] $middlewareForWriting
	 *
	 * @return void
	 */
	static function createRoutes(
		App                 $app,
		string              $entityName,
		string              $controllerClassNameOrToken,
		$middlewareForReading = null,
		$middlewareForWriting = null
	) {

		$pathPrefix = '/' . $entityName;

		$readGroup = $app->group($pathPrefix, function (RouteCollectorProxy $group) use ($app, $controllerClassNameOrToken) {

			$group->get('/many/{idAsStringWithCommas:[0-9,]+}', array($controllerClassNameOrToken, 'viewMany'));
			$group->get('/{id:[0-9]+}', array($controllerClassNameOrToken, 'view'));
			$group->get('', array($controllerClassNameOrToken, 'list'));

		});
		if ($middlewareForReading) {
			$middlewareForReading = Arrays::arrayize($middlewareForReading);
			foreach ($middlewareForReading as $middleware) {
				$readGroup->addMiddleware($middleware);
			}
		}

		$writeGroup = $app->group($pathPrefix, function (RouteCollectorProxy $group) use ($app, $controllerClassNameOrToken) {

			$group->post('', array($controllerClassNameOrToken, 'create'));
			$group->patch('/{id:[0-9]+}', array($controllerClassNameOrToken, 'edit'));
			$group->delete('/{id:[0-9]+}', array($controllerClassNameOrToken, 'delete'));
			$group->delete('', array($controllerClassNameOrToken, 'deleteMany'));
			$group->post('/{id:[0-9]+}', array($controllerClassNameOrToken, 'clone'));

		});
		if ($middlewareForWriting) {
			$middlewareForWriting = Arrays::arrayize($middlewareForWriting);
			foreach ($middlewareForWriting as $middleware) {
				$writeGroup->addMiddleware($middleware);
			}

		}

	}

}
