<?php

namespace OndraKoupil\AppTools\Maps;

class MapyCzCachingGeocoder extends MapyCzGeocoder {

	protected $cacheFile;

	protected $cacheData = array(
		'addr' => array(),
		'coords' => array(),
	);

	/**
	 * @param $cacheFile
	 */
	public function __construct($cacheFile) {
		$this->cacheFile = $cacheFile;
		$this->loadFromCache();
	}

	public function geocodeFromAddress($address) {
		if (array_key_exists($address, $this->cacheData['addr'])) {
			return $this->cacheData['addr'][$address];
		}
		$addr = parent::geocodeFromAddress($address);
		$this->cacheData['addr'][$address] = $addr;
		return $addr;
	}

	public function geocodeFromCoords(Coords $coords) {
		$code = $coords->lat . ';' . $coords->lon;
		if (array_key_exists($code, $this->cacheData['coords'])) {
			return $this->cacheData['addr'][$code];
		}
		$coded = parent::geocodeFromCoords($coords);
		$this->cacheData['coords'][$code] = $coded;
		return $coded;
	}

	protected function loadFromCache() {
		$loaded = @file_get_contents($this->cacheFile);
		if ($loaded) {
			$decoded = unserialize($loaded);
			if (isset($decoded['addr']) and isset($decoded['coords'])) {
				$this->cacheData = $decoded;
			}
		}
	}

	public function saveCachedData() {
		file_put_contents($this->cacheFile, serialize($this->cacheData));
	}


}
