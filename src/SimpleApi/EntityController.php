<?php

namespace OndraKoupil\AppTools\SimpleApi;

use Exception;
use OndraKoupil\Tools\Arrays;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EntityController {

	/**
	 * @var EntityManagerInterface
	 */
	private $manager;

	function __construct(
		EntityManagerInterface $manager
	) {

		$this->manager = $manager;
	}

	function respondWithNothing(ResponseInterface $response): ResponseInterface {
		return $response->withStatus(204);
	}

	function respondWithJson(ResponseInterface $response, $data): ResponseInterface {
		$r = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
		$r->getBody()->write(json_encode($data));
		return $r;
	}

	function respondWithError(ResponseInterface $response, int $status, string $errorDesc): ResponseInterface {
		$r = $response->withStatus($status)->withHeader('Content-Type', 'application/json');
		$r->getBody()->write(json_encode(array('error' => $errorDesc)));
		return $r;
	}

	function list(ServerRequestInterface $request, ResponseInterface $response, string $partsAsString = ''): ResponseInterface {
		$parts = self::convertPartsStringToArray($partsAsString);
		return $this->respondWithJson($response, $this->manager->getAllItems($parts));
	}

	function view(ServerRequestInterface $request, ResponseInterface $response, string $id, string $partsAsString = ''): ResponseInterface {
		try {
			$parts = self::convertPartsStringToArray($partsAsString);
			$data = $this->manager->getItem($id, $parts);
			return $this->respondWithJson($response, $data);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' was not found.');
		}
	}

	function viewMany(ServerRequestInterface $request, ResponseInterface $response, string $idAsStringWithCommas, string $partsAsString = ''): ResponseInterface {
		try {
			$ids = array_values(array_map('trim', explode(',', $idAsStringWithCommas)));
			$parts = self::convertPartsStringToArray($partsAsString);
			$data = $this->manager->getManyItems($ids, $parts);
			return $this->respondWithJson($response, $data);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' was not found.');
		}
	}


	function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
		$data = $request->getParsedBody();
		if (!$data) {
			return $this->respondWithError($response, 400, 'Missing body with data.');
		}
		$item = $this->manager->createItem($data);
		return $this->respondWithJson($response, $item);
	}

	function delete(ServerRequestInterface $request, ResponseInterface $response, string $id): ResponseInterface {
		try {
			$this->manager->deleteItem($id);
			return $this->respondWithNothing($response);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' was not found.');
		}
	}

	function deleteMany(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
		$body = $request->getParsedBody();
		if (!($body['id'] ?? null)) {
			return $this->respondWithError($response, 400, 'Missing [id] parameter with ID\'s to delete.');
		}
		$ids = Arrays::arrayize($body['id']);
		try {
			$this->manager->deleteManyItems($ids);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' not found.');
		}
		return $this->respondWithNothing($response);

	}

	function edit(ServerRequestInterface $request, ResponseInterface $response, string $id): ResponseInterface {
		$data = $request->getParsedBody();
		if (!$data) {
			return $this->respondWithError($response, 400, 'Missing body with data.');
		}
		try {
			$this->manager->updateItem($id, $data);
			return $this->respondWithNothing($response);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' was not found.');
		}
	}

	function clone(ServerRequestInterface $request, ResponseInterface $response, string $id): ResponseInterface {
		try {
			$created = $this->manager->cloneItem($id);
			return $this->respondWithJson($response, $created);
		} catch (ItemNotFoundException $e) {
			return $this->respondWithError($response, 404, 'Item with ID ' . $e->notFoundId . ' was not found.');
		}
	}

	static protected function convertPartsStringToArray(string $partsAsString): array {
		return array_fill_keys(array_filter(array_map('trim', explode(',', $partsAsString))), true);
	}

}
