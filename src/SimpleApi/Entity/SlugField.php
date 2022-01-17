<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

class SlugField {

	/**
	 * @var string
	 */
	public $fieldName;

	/**
	 * @var string[]
	 */
	public $basedOnFields;

	/**
	 * @var bool
	 */
	public $syncAfterCreating;

	/**
	 * @param string $fieldName
	 * @param string[] $basedOnFields
	 * @param bool $syncAfterCreating
	 */
	public function __construct(string $fieldName, array $basedOnFields, bool $syncAfterCreating = false) {
		$this->fieldName = $fieldName;
		$this->basedOnFields = $basedOnFields;
		$this->syncAfterCreating = $syncAfterCreating;
	}


}
