<?php

namespace OndraKoupil\AppTools\Maps;

class MapyCzGeocoder implements IAddressToCoords, ICoordsToAddress {

	public function geocodeFromAddress($address) {
		// https://api.mapy.cz/geocode?query=Radlick%C3%A1%202
		$url = 'https://api.mapy.cz/geocode?query=' . rawurlencode($address);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);

		if ($response) {

			$xml = simplexml_load_string($response);

			if ($xml) {
				$item = $xml->point->item[0];
				if ($item and $item['x'] and $item['y']) {
					return new Coords((string)$item['y'], (string)$item['x']);
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

		$url = 'https://api.mapy.cz/rgeocode?lon=' . rawurlencode($coords->lon) . '&lat=' . rawurlencode($coords->lat);
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);

		if ($response) {
			$xml = simplexml_load_string($response);
			if ($xml and $xml->item) {

				$address = new StructuredAddress();

				foreach($xml->item as $item) {
					$name = (string)$item['name'];
					switch ((string)$item['type']) {
						case 'addr':
							if ($address->streetWithNumber) {
								$address->streetWithNumber .= ', ';
							}
							$address->streetWithNumber .= $name;
							break;

						case 'stre':
							if ($address->street) {
								$address->street .= ', ';
							}
							$address->street .= $name;
							break;

						case 'quar':
						case 'ward':
							if ($address->cityPart) {
								$address->cityPart .= ', ';
							}
							$address->cityPart .= $name;
							break;

						case 'muni':
						case 'osmm':
							if ($address->city) {
								$address->city .= ', ';
							}
							$address->city .= $name;
							break;

						case 'dist':
							if ($address->district) {
								$address->district .= ', ';
							}
							$address->district .= $name;
							break;

						case 'region':
						case 'osmr':
							if ($address->region) {
								$address->region .= ', ';
							}
							$address->region .= $name;
							break;

						case 'coun':
						case 'osmc':
							if ($address->country) {
								$address->country .= ', ';
							}
							$address->country .= $name;
							break;
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
