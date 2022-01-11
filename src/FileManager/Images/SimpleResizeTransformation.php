<?php

namespace OndraKoupil\AppTools\FileManager\Images;

use InvalidArgumentException;
use RuntimeException;

class SimpleResizeTransformation implements ImageTransformationInterface {

	const COVER = 'cover';
	const CONTAIN = 'contain';
	const CROP = 'crop';
	const STRETCH = 'stretch';

	protected int $width;

	protected int $height;

	protected string $mode;

	function __construct(int $width, int $height, string $mode = self::CONTAIN) {
		$this->width = $width;
		$this->height = $height;
		$this->mode = $mode;
	}

	function transform($resource) {

		$origWidth = imagesx($resource);
		$origHeight = imagesy($resource);

		switch ($this->mode) {

			case self::STRETCH:
				if ($this->width === 0 or $this->height === 0) {
					throw new InvalidArgumentException('In stretch mode, both width and height must be set.');
				}
				$canvas = imagecreatetruecolor($this->width, $this->height);
				imagecopyresampled($canvas, $resource, 0, 0, 0, 0, $this->width, $this->height, $origWidth, $origHeight);
				break;

			case self::CONTAIN:
			case self::COVER:
				if (!$this->width and !$this->height) {
					throw new InvalidArgumentException('In ' . $this->mode . ' mode, at least width or height must be set.');
				}
				if ($origWidth < $this->width and $origHeight < $this->height) {
					// Smaller in both ways - keep intact
					$canvas = $resource;
				} else {

					$widthRatio = $this->width / $origWidth;
					$heightRatio = $this->height / $origHeight;

					if ($this->mode === self::COVER) {
						$targetRatio = max($widthRatio, $heightRatio);
					} else {
						if (!$widthRatio) {
							$targetRatio = $heightRatio;
						} elseif (!$heightRatio) {
							$targetRatio = $widthRatio;
						} else {
							$targetRatio = min($widthRatio, $heightRatio);
						}
					}

					$targetWidth = ceil($origWidth * $targetRatio);
					$targetHeight = ceil($origHeight * $targetRatio);

					$canvas = imagecreatetruecolor($targetWidth, $targetHeight);
					imagecopyresampled($canvas, $resource, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);
				}

				break;

			case self::CROP:

				throw new RuntimeException('Not implemented (yet!)');

			default:
				throw new InvalidArgumentException('Invalid resize mode: ' . $this->mode);

		}

		return $canvas;

	}


}
