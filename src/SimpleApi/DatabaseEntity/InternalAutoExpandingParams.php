<?php

namespace OndraKoupil\AppTools\SimpleApi\DatabaseEntity;

/**
 * @internal
 */
class InternalAutoExpandingParams {

	/**
	 * @var string Key in Context that should trigger autoexpansion
	 */
	public $contextKey;

	/**
	 * @var string Other entity (subitem, parent...) ID
	 */
	public $entityId;

	/**
	 * @var mixed What to give as context for the other entity to further expand itself
	 */
	public $expandContext;

	/**
	 * @var string Which field in the original item should the expanded data be saved to. Default to $contextKey
	 */
	public $field;

	/**
	 * ${CARET}
	 *
	 * @param string $contextKey
	 * @param string $entityId
	 * @param mixed $expandContext
	 * @param string $field Default = same as $contextKey
	 */
	public function __construct(string $contextKey, string $entityId, $expandContext = null, string $field = '') {
		$this->contextKey = $contextKey;
		$this->entityId = $entityId;
		$this->expandContext = $expandContext;
		$this->field = $field ?: $contextKey;
	}


}
