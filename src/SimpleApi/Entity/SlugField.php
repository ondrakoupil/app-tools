<?php

namespace OndraKoupil\AppTools\SimpleApi\Entity;

class SlugField {

	public string $fieldName;

	/**
	 * @var string[]
	 */
	public array $basedOnFields;

	public bool $syncAfterCreating;

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
