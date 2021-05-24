<?php

namespace OndraKoupil\AppTools\Maps;

use OndraKoupil\Tools\Strings;

class Coords {

	public $lat;

	public $lon;

	/**
	 * @param $lat
	 * @param $lon
	 */
	public function __construct($lat, $lon) {
		$this->lat = Strings::number($lat);
		$this->lon = Strings::number($lon);
	}


}
