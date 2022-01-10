<?php

namespace OndraKoupil\AppTools\FileManager\Images;

use RuntimeException;

class SimpleResizeTransformation implements ImageTransformationInterface {

	private int $width;

	private int $height;

	private int $quality;

	private string $outFormat;

	/**
	 *
	 *
	 * @param int $width
	 * @param int $height
	 */
	function __construct(int $width, int $height, $quality = 85, $outFormat = '') {
		$this->width = $width;
		$this->height = $height;
		$this->quality = $quality;
		$this->outFormat = $outFormat;
	}

	function transform(string $path): void {

		$type = mime_content_type($path);

		if ($type === 'image/svg+xml') {
			return;
		}

		$resource = null;

		switch ($type) {
			case 'image/jpeg':
				$resource = imagecreatefromjpeg($path);
				break;

			case 'image/png':
				$resource = imagecreatefrompng($path);
				break;

			case 'image/webp':
				$resource = imagecreatefromwebp($path);
				break;

			default:
				throw new RuntimeException('Unknown format: ' . $type);
		}

		$origWidth = imagesx($resource);
		$origHeight = imagesy($resource);
		$canvas = imagecreatetruecolor($this->width, $this->height);

		imagecopyresampled($canvas, $resource, 0, 0, 0, 0, $this->width, $this->height, $origWidth, $origHeight);

		$outputType = $this->outFormat ?: $type;

		switch ($outputType) {
			case 'image/jpeg':
				imagejpeg($canvas, $path, $this->quality ?: 85);
				break;

			case 'image/png':
				imagepng($canvas, $path);
				break;

			case 'image/webp':
				imagewebp($canvas, $path, $this->quality);
				break;
		}


	}


}
