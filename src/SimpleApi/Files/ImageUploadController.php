<?php

namespace OndraKoupil\AppTools\SimpleApi\Files;

use OndraKoupil\AppTools\FileManager\FileManager;
use OndraKoupil\AppTools\FileManager\PreresizedImageFileManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ImageUploadController {

	const CONTENT = 'content';
	const NAME = 'name';

	/**
	 * @var PreresizedImageFileManager
	 */
	protected $imageManager;

	/**
	 * @var string
	 */
	protected $contentFieldName;

	/**
	 * @var string
	 */
	protected $filenameFieldName;


	function __construct(
		PreresizedImageFileManager $imageManager,
		string $contentFieldName = self::CONTENT,
		string $filenameFieldName = self::NAME
	) {

		$this->imageManager = $imageManager;
		$this->contentFieldName = $contentFieldName;
		$this->filenameFieldName = $filenameFieldName;
	}

	/**
	 * @return FileManager
	 */
	public function getFileManager(): FileManager {
		return $this->imageManager->getFileManager();
	}

	/**
	 * @return PreresizedImageFileManager
	 */
	public function getImageManager(): PreresizedImageFileManager {
		return $this->imageManager;
	}

	public function upload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {

		$body = $request->getParsedBody();

		$content = $body[$this->contentFieldName] ?? '';
		$name = $body[$this->filenameFieldName] ?? '';

		if (!$content or !$name) {
			$r = $response->withStatus(400)->withHeader('Content-Type', 'application/json');
			$r->getBody()->write(json_encode(array('error' => 'Missing fields: ' . $this->filenameFieldName . ' or ' . $this->contentFieldName), JSON_THROW_ON_ERROR));
			return $r;
		}

		if (substr($content, 0, 5) === 'data:') {
			$colonPos = strpos($content, ',');
			$beforeColon = substr($content, 0, $colonPos);
			$payload = substr($content, $colonPos + 1);
			if (substr($beforeColon, -6) === 'base64') {
				$payload = base64_decode($payload);
			}
		} else {
			$payload = base64_decode($content);
		}

		$resultingName = $this->imageManager->addImage($payload, $name);
		$resultUrls = $this->imageManager->getImageAllUrls($resultingName);

		$r = $response->withStatus(200)->withHeader('Content-Type', 'application/json');
		$r->getBody()->write(json_encode(array('filename' => $resultingName, 'urls' => $resultUrls), JSON_THROW_ON_ERROR));
		return $r;

	}



}
