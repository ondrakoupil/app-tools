<?php

namespace OndraKoupil\AppTools\SimpleApi;

use InvalidArgumentException;
use OndraKoupil\Tools\Arrays;
use Psr\Http\Server\MiddlewareInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class RouteCreator {

	const ROUTE_LIST = 'list';
	const ROUTE_GET_MANY = 'view-many';
	const ROUTE_GET = 'view';
	const ROUTE_CREATE = 'create';
	const ROUTE_EDIT = 'edit';
	const ROUTE_DELETE = 'delete';
	const ROUTE_DELETE_MANY = 'many';
	const ROUTE_CLONE = 'clone';

	const ALL_ROUTES = array(
		self::ROUTE_LIST,
		self::ROUTE_GET,
		self::ROUTE_GET_MANY,
		self::ROUTE_CREATE,
		self::ROUTE_EDIT,
		self::ROUTE_DELETE,
		self::ROUTE_DELETE_MANY,
		self::ROUTE_CLONE,
	);

	/**
	 * @param array $definitions
	 *
	 * @return array [route_code] => true
	 */
	static function parseDefinitionWhichRouter(array $definitions): array {

		if (!$definitions) {
			return array_fill_keys(self::ALL_ROUTES, true);
		}

		$hasAnyTrue = false;
		$hasAnyFalse = false;

		foreach ($definitions as $item) {
			if ($item === true) {
				$hasAnyTrue = true;
			} elseif ($item === false) {
				$hasAnyFalse = true;
			} else {
				throw new InvalidArgumentException('$definitions can contain only true or false');
			}
		}

		if ($hasAnyFalse and $hasAnyTrue) {
			throw new InvalidArgumentException('$definitions can contain only trues or falses, but not both.');
		}
		

		if ($hasAnyTrue) {
			return array_fill_keys(array_keys($definitions), true);
		} else {
			$all = array_fill_keys(self::ALL_ROUTES, true);
			foreach ($definitions as $definition => $false) {
				unset($all[$definition]);
			}
			return array_fill_keys(array_keys($all), true);
		}
	}

	/**
	 *
	 *
	 * @param App $app
	 * @param string $entityName
	 * @param string $controllerClassNameOrToken
	 * @param MiddlewareInterface|MiddlewareInterface[] $middlewareForReading
	 * @param MiddlewareInterface|MiddlewareInterface[] $middlewareForWriting
	 * @param array $whichRoutesDefinition See self::parseDefinitionWhichRouter()
	 * @return void
	 */
	static function createRoutes(
		App                 $app,
		string              $entityName,
		string              $controllerClassNameOrToken,
		$middlewareForReading = null,
		$middlewareForWriting = null,
		$whichRoutesDefinition = array()
	) {

		$pathPrefix = '/' . $entityName;

		$whichRoutesDefinition = self::parseDefinitionWhichRouter($whichRoutesDefinition);

		$readGroup = $app->group($pathPrefix, function (RouteCollectorProxy $group) use ($app, $controllerClassNameOrToken, $whichRoutesDefinition) {

			if (isset($whichRoutesDefinition[self::ROUTE_GET_MANY])) {
				$group->get('/many/{idAsStringWithCommas:[0-9,]+}', array($controllerClassNameOrToken, 'viewMany'));
			}
			if (isset($whichRoutesDefinition[self::ROUTE_GET])) {
				$group->get('/{id:[0-9]+}', array($controllerClassNameOrToken, 'view'));
			}
			if (isset($whichRoutesDefinition[self::ROUTE_LIST])) {
				$group->get('', array($controllerClassNameOrToken, 'list'));
			}

		});
		if ($middlewareForReading) {
			$middlewareForReading = Arrays::arrayize($middlewareForReading);
			foreach ($middlewareForReading as $middleware) {
				$readGroup->addMiddleware($middleware);
			}
		}

		$writeGroup = $app->group($pathPrefix, function (RouteCollectorProxy $group) use ($app, $controllerClassNameOrToken, $whichRoutesDefinition) {

			if (isset($whichRoutesDefinition[self::ROUTE_CREATE])) {
				$group->post('', array($controllerClassNameOrToken, 'create'));
			}

			if (isset($whichRoutesDefinition[self::ROUTE_EDIT])) {
				$group->patch('/{id:[0-9]+}', array($controllerClassNameOrToken, 'edit'));
			}

			if (isset($whichRoutesDefinition[self::ROUTE_DELETE])) {
				$group->delete('/{id:[0-9]+}', array($controllerClassNameOrToken, 'delete'));
			}

			if (isset($whichRoutesDefinition[self::ROUTE_DELETE_MANY])) {
				$group->delete('', array($controllerClassNameOrToken, 'deleteMany'));
			}

			if (isset($whichRoutesDefinition[self::ROUTE_CLONE])) {
				$group->post('/{id:[0-9]+}', array($controllerClassNameOrToken, 'clone'));
			}

		});
		if ($middlewareForWriting) {
			$middlewareForWriting = Arrays::arrayize($middlewareForWriting);
			foreach ($middlewareForWriting as $middleware) {
				$writeGroup->addMiddleware($middleware);
			}

		}

	}

}
