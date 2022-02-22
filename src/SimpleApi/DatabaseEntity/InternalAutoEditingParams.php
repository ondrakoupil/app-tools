<?php

namespace OndraKoupil\AppTools\SimpleApi\DatabaseEntity;

/**
 * @internal
 */
class InternalAutoEditingParams {

	/**
	 * @var string
	 */
	public $keySet;

	/**
	 * @var string
	 */
	public $keyAdd;

	/**
	 * @var string
	 */
	public $keyDelete;

	/**
	 * @var string
	 */
	public $keyClear;

	/**
	 * @var string Other entity (subitem, parent...) ID
	 */
	public $entityId;

	/**
	 * ${CARET}
	 *
	 * @param string $keySet
	 * @param string $keyAdd
	 * @param string $keyDelete
	 * @param string $keyClear
	 * @param string $entityId
	 */
	public function __construct(string $keySet, string $keyAdd, string $keyDelete, string $keyClear, string $entityId) {
		$this->keySet = $keySet;
		$this->keyAdd = $keyAdd;
		$this->keyDelete = $keyDelete;
		$this->keyClear = $keyClear;
		$this->entityId = $entityId;
	}


}
