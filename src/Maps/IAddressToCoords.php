<?php


namespace OndraKoupil\AppTools\Maps;


interface IAddressToCoords {

	/**
	 * @param string $address
	 * @return Coords
	 */
	public function geocodeFromAddress($address);

}
