<?php

namespace OndraKoupil\AppTools\FileManager\Images;

class ImageDimensions {
	public $w = 0, $h = 0;

	/**
	 * @param int $w
	 * @param int $h
	 */
	public function __construct(int $w, int $h) {
		$this->w = $w;
		$this->h = $h;
	}


}
