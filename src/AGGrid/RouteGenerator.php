<?php

namespace OndraKoupil\AppTools\AGGrid;

use OndraKoupil\Tools\Arrays;
use Slim\App;

class RouteGenerator {

	public static function generateRoutes(
		App $app,
		$path,
		$containerKey,
		$middlewares = array()
	) {

		$middlewares = Arrays::arrayize($middlewares);

		$route = $app->get($path, $containerKey . ':get');
		foreach ($middlewares as $middleware) {
			$route->add($middleware);
		}

		$route = $app->delete($path, $containerKey . ':delete');
		foreach ($middlewares as $middleware) {
			$route->add($middleware);
		}

		$route = $app->post($path, $containerKey . ':create');
		foreach ($middlewares as $middleware) {
			$route->add($middleware);
		}

		$route = $app->patch($path, $containerKey . ':update');
		foreach ($middlewares as $middleware) {
			$route->add($middleware);
		}
	}

}
