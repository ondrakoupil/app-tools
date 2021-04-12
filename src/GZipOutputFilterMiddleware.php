<?php

namespace OndraKoupil\AppTools;

use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Stream;

class GZipOutputFilterMiddleware {

	/**
	 * @var bool
	 */
	private $enable;

	function __construct($enable = true) {
		$this->enable = $enable;
	}

	// https://discourse.slimframework.com/t/compressing-text-gzip/2328

	function __invoke(Request $request, Response $response, callable $next) {

		if (!$this->enable) {
			return $next($request, $response);
		}

		if (!$request->hasHeader('Accept-Encoding') or $request->hasHeader('Accept-Encoding') and stristr($request->getHeaderLine('Accept-Encoding'), 'gzip') === false) {
			// Browser doesn't accept gzip compression
			return $next($request, $response);
		}

		/** @var Response $response */
		$response = $next($request, $response);

		if ($response->hasHeader('Content-Encoding')) {
			return $next($request, $response);
		}

		if ($response->getBody()->getSize() < 100) {
			// Too small to be effectively encoded
			return $next($request, $response);
		}

		// Compress response data
		$deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
		$compressed = deflate_add($deflateContext, (string)$response->getBody(), \ZLIB_FINISH);

		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $compressed);
		rewind($stream);

		return $response
			->withHeader('Content-Encoding', 'gzip')
			->withHeader('Content-Length', strlen($compressed))
			->withBody(new Stream($stream));

	}

}
