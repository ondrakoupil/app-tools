<?php

namespace OndraKoupil\AppTools\Maps;

class StructuredAddress {

	public $streetWithNumber;

	public $street;

	public $cityPart;

	public $city;

	public $district;

	public $region;

	public $country;

	public $name;

	/**
	 * @param string $name
	 * @param string $streetWithNumber
	 * @param string $street
	 * @param string $cityPart
	 * @param string $city
	 * @param string $district
	 * @param string $region
	 * @param string $country
	 */
	public function __construct($name = '', $streetWithNumber = '', $street = '', $cityPart = '', $city = '', $district = '', $region = '', $country = '') {
		$this->name = $name;
		$this->streetWithNumber = $streetWithNumber;
		$this->street = $street;
		$this->cityPart = $cityPart;
		$this->city = $city;
		$this->district = $district;
		$this->region = $region;
		$this->country = $country;
	}

	public function __toString() {

		$streetPart = $this->streetWithNumber ?: $this->street;
		$cityPart = $this->cityPart;
		if (!$cityPart) {
			$this->cityPart = $this->city;
		} else {
			if ($this->city !== $this->cityPart) {
				$cityPart = $this->cityPart . ', ' . $this->city;
			}
		}
		$countryPart = $this->country;

		$parts = array();
		if ($streetPart) {
			$parts[] = $streetPart;
		}
		if ($cityPart) {
			$parts[] = $cityPart;
		}
		if ($countryPart) {
			$parts[] = $countryPart;
		}

		return implode(', ', $parts);
	}


}
