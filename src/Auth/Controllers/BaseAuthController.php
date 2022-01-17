<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Psr\Http\Message\ResponseInterface;

/**
 * Handles returning data as JSON
 */
class BaseAuthController {

	protected function respondWith(ResponseInterface $response, $data): ResponseInterface {
		$response = $response->withHeader('Content-Type', 'application/json');
		$response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
		return $response;
	}

}
