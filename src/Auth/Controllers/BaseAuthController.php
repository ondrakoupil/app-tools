<?php

namespace OndraKoupil\AppTools\Auth\Controllers;

use Psr\Http\Message\ResponseInterface;

/**
 * Handles returning data as JSON
 */
class BaseAuthController {

	protected function respondWith(ResponseInterface $response, $data): ResponseInterface {
		$response = $response->withHeader('Content-Type', 'application/json');
		print_r($data);
		$response->getBody()->write(json_encode($data));
		return $response;
	}

}
