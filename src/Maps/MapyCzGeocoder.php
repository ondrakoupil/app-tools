<?php

namespace OndraKoupil\AppTools\Maps;

class MapyCzGeocoder implements IAddressToCoords, ICoordsToAddress {

	protected $apiKey;

	function __construct($apiKey = null) {
		$this->apiKey = $apiKey;
	}

	public function geocodeFromAddress($address) {
		$url = 'https://api.mapy.com/v1/geocode?query=' . rawurlencode($address) . '&apikey=' . rawurlencode($this->apiKey);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);

		if ($response) {

			$parsed = json_decode($response, true);

			if ($parsed) {
				$position = $parsed['items'][0]['position'] ?? null;
				if ($position and $position['lat'] and $position['lon']) {
					return new Coords((string)$position['lat'], (string)$position['lon']);
				}
			}

			return null;

		} else {
			return null;
		}
	}

	public function geocodeFromCoords(Coords $coords) {

		// https://api.mapy.cz/rgeocode?lon=14.342884&lat=50.087576&count=0

		if (!$coords->lat or !$coords->lon) {
			return null;
		}

		$url = 'https://api.mapy.com/v1/rgeocode?lon=' . rawurlencode($coords->lon) . '&lat=' . rawurlencode($coords->lat) . '&apikey=' . rawurlencode($this->apiKey);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);

		if ($response) {
			$parsed = json_decode($response, true);
			if ($parsed and $parsed['items']) {

				$address = new StructuredAddress();

				foreach($parsed['items'] as $item) {
					$name = (string)$item['name'];
					$address->name = $name;
					foreach ($item['regionalStructure'] as $structureItem) {
						switch ($structureItem['type']) {
							case 'regional.address':
								$address->streetWithNumber = $structureItem['name'];
								break;
							case 'regional.street':
								$address->street = $structureItem['name'];
								break;
							case 'regional.municipality_part':
								$address->cityPart = $structureItem['name'];
								break;
							case 'regional.municipality':
								$address->city = $structureItem['name'];
								break;
							case 'regional.region':
								$address->region = $structureItem['name'];
								break;
							case 'regional.country':
								$address->country = $structureItem['name'];
								break;
						}
					}
				}

				return $address;
			}

			return null;

		} else {
			return null;
		}

		/*
		 <?xml version="1.0" encoding="utf-8"?>
<rgeocode label="Na Petřinách 1716/63, Praha, 162 00, Hlavní město Praha" status="200" message="Ok">
    <item id="8974602" name="Na Petřinách 1716/63" source="addr" type="addr" x="14.342960267517336" y="50.087475071144254" />
    <item id="120796" name="Na Petřinách" source="stre" type="stre" x="14.365349353936695" y="50.091989935266255" />
    <item id="107" name="Praha 6" source="quar" type="quar" x="14.3257013889" y="50.0902633333" />
    <item id="14948" name="Břevnov" source="ward" type="ward" x="14.369570809242507" y="50.08571719089053" />
    <item id="3468" name="Praha" source="muni" type="muni" x="14.4341412988" y="50.0835493857" />
    <item id="47" name="Okres Hlavní město Praha" source="dist" type="dist" x="14.466000012384795" y="50.066789200146815" />
    <item id="10" name="Hlavní město Praha" source="regi" type="regi" x="14.466" y="50.066789" />
    <item id="112" name="Česko" source="coun" type="coun" x="15.338411" y="49.742858" />
</rgeocode>
		 */

	}


}
