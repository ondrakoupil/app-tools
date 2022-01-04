<?php

namespace OndraKoupil\AppTools\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds GZip compression support
 *
 * @see https://discourse.slimframework.com/t/compressing-text-gzip/2328
 */
class GZipOutputFilterMiddleware implements MiddlewareInterface {

	/**
	 * @var bool
	 */
	private $enable;

	/**
	 * @var callable
	 */
	private $streamCreator;

	/**
	 * @param bool $enable
	 * @param callable $streamCreator function($fileStream): StreamInterface
	 */
	function __construct(bool $enable, callable $streamCreator) {
		$this->enable = $enable;
		$this->streamCreator = $streamCreator;
	}

	function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {

		$response = $handler->handle($request);

		// Disabled
		if (!$this->enable) {
			return $response;
		}

		if (!$request->hasHeader('Accept-Encoding') or $request->hasHeader('Accept-Encoding') and stristr($request->getHeaderLine('Accept-Encoding'), 'gzip') === false) {
			// Browser doesn't accept gzip compression
			return $response;
		}

		// Is already somehow encoded
		if ($response->hasHeader('Content-Encoding')) {
			return $response;
		}

		if ($response->getBody()->getSize() < 100) {
			// Too small to be effectively encoded
			return $response;
		}

		// Compress response data
		$deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
		$compressed = deflate_add($deflateContext, (string)$response->getBody(), \ZLIB_FINISH);

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $compressed);
		rewind($stream);

		$streamCreator = $this->streamCreator;
		/** @var StreamInterface $streamWrapper */
		$streamWrapper = $streamCreator($stream);

		return $response
			->withHeader('Content-Encoding', 'gzip')
			->withHeader('Content-Length', strlen($compressed))
			->withBody($streamWrapper);

	}

}
