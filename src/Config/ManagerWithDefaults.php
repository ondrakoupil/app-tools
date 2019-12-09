<?php

namespace OndraKoupil\AppTools\Config;

abstract class ManagerWithDefaults extends BaseManager {


	protected $defaultValues = array();

	/**
	 * @var bool
	 */
	protected $strict;

	function __construct($defaultValues, $strict = false) {
		$this->defaultValues = $defaultValues;
		$this->strict = $strict;
	}

	/**
	 * @return bool
	 */
	public function isStrict(): bool {
		return $this->strict;
	}

	/**
	 * @param bool $strict
	 */
	public function setStrict(bool $strict): void {
		$this->strict = $strict;
	}

	/**
	 * @param array $defaultValues
	 */
	public function setNewDefaultValues(array $defaultValues): void {
		$this->defaultValues = $defaultValues;
	}

	public function setDefaultValue($key, $value) {
		$this->defaultValues[$key] = $value;
	}

	public function patchDefaultValues(array $values) {
		foreach ($values as $key => $value) {
			$this->defaultValues[$key] = $value;
		}
	}

	public function getDefault($key, $userGivenDefault = null) {
		if ($userGivenDefault !== null) {
			return $userGivenDefault;
		}
		if (array_key_exists($key, $this->defaultValues)) {
			return $this->defaultValues[$key];
		}

		return null;
	}

	public function isValidKey($key) {
		if ($this->strict and !array_key_exists($key, $this->defaultValues)) {
			return false;
		}

		return true;
	}

}
