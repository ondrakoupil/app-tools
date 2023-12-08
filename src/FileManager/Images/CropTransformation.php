<?php

namespace OndraKoupil\AppTools\FileManager\Images;

use InvalidArgumentException;
use RuntimeException;

class CropTransformation implements ImageTransformationInterface {

	/**
	 * @var float
	 */
	protected $x1;

	/**
	 * @var float
	 */
	protected $x2;

	/**
	 * @var float
	 */
	protected $y1;

	/**
	 * @var float
	 */
	protected $y2;

	function __construct(float $x1, float $x2, float $y1, float $y2) {
		$this->setCrop($x1, $x2, $y1, $y2);
	}

	function setCrop($x1, $x2, $y1, $y2) {
		if ($x1 < 0) {
			$x1 = 0;
		}
		if ($x2 > 1) {
			$x2 = 1;
		}
		if ($y1 < 0) {
			$y1 = 0;
		}
		if ($y2 > 1) {
			$y2 = 1;
		}
		if ($x1 > $x2) {
			$x1 = $x2;
		}
		if ($y1 > $y2) {
			$y1 = $y2;
		}
		$this->x1 = $x1;
		$this->x2 = $x2;
		$this->y1 = $y1;
		$this->y2 = $y2;
	}

	function transform($resource) {

		$origWidth = imagesx($resource);
		$origHeight = imagesy($resource);

		$absX = floor($origWidth * $this->x1);
		$absY = floor($origHeight * $this->y1);
		$newWidth = ceil($origWidth * ($this->x2 - $this->x1));
		$newHeight = ceil($origHeight * ($this->y2 - $this->y1));

		$canvas = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($canvas, $resource, 0, 0, $absX, $absY, $newWidth, $newHeight, $newWidth, $newHeight);

		return $canvas;

	}


}
