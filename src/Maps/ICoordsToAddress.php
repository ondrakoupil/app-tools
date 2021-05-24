<?php


namespace OndraKoupil\AppTools\Maps;


interface ICoordsToAddress {

	/**
	 * @param Coords $coords
	 * @return StructuredAddress
	 */
	public function geocodeFromCoords(Coords $coords);

}
