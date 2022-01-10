<?php

namespace OndraKoupil\AppTools\SimpleApi\Files;

use OndraKoupil\AppTools\FileManager\FileManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FileUploadController {

	const CONTENT = 'content';
	const NAME = 'name';

	protected FileManager $fileManager;

	private string $contentFieldName;

	private string $filenameFieldName;

	/**
	 * @var string[]
	 */
	protected array $context;


	/**
	 * @param FileManager $fileManager
	 * @param string $contentFieldName
	 * @param string $filenameFieldName
	 * @param array $context
	 */
	function __construct(
		FileManager $fileManager,
		string $contentFieldName = self::CONTENT,
		string $filenameFieldName = self::NAME,
		array $context = array()
	) {
		$this->fileManager = $fileManager;
		$this->contentFieldName = $contentFieldName;
		$this->filenameFieldName = $filenameFieldName;
		$this->context = $context;
	}

	/**
	 * @return FileManager
	 */
	public function getFileManager(): FileManager {
		return $this->fileManager;
	}

	public function upload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

		$body = $request->getParsedBody();

		$content = $body[$this->contentFieldName] ?? '';
		$name = $body[$this->filenameFieldName] ?? '';

		if (!$content or !$name) {
			$r = $response->withStatus(400)->withHeader('Content-Type', 'application/json');
			$r->getBody()->write(json_encode(array('error' => 'Missing fields: ' . $this->filenameFieldName . ' or ' . $this->contentFieldName)));
			return $r;
		}

		$resultingName = $this->fileManager->addFile($name, $content, $this->context);
		$url = $this->fileManager->getUrlOfFile($resultingName, $this->context);

		$r = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
		$r->getBody()->write(json_encode(array('filename' => $resultingName, 'url' => $url)));
		return $r;

	}



}
