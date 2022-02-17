<?php

namespace OndraKoupil\AppTools\SimpleApi\DatabaseEntity;

use InvalidArgumentException;
use OndraKoupil\AppTools\SimpleApi\EntityManagerInterface;
use OndraKoupil\AppTools\SimpleApi\Relations\MultiRelationManager;
use OndraKoupil\AppTools\SimpleApi\Relations\SubItemsManager;
use Exception;

class InternalMultiRelationParams {

	/**
	 * @var string
	 */
	public $entityId;

	/**
	 * @var MultiRelationManager
	 */
	public $relationManager;

	/**
	 * @var EntityManagerInterface
	 */
	public $entityManager;

	/**
	 * @var callable
	 */
	public $entityManagerGetter;

	/**
	 * @param string $entityId
	 * @param MultiRelationManager $relationManager
	 * @param EntityManagerInterface|callable $entityManagerOrGetter
	 */
	public function __construct(string $entityId, MultiRelationManager $relationManager, $entityManagerOrGetter) {
		$this->entityId = $entityId;
		$this->relationManager = $relationManager;

		if ($entityManagerOrGetter instanceof EntityManagerInterface) {
			$this->entityManager = $entityManagerOrGetter;
		} else if (is_callable($entityManagerOrGetter)) {
			$this->entityManagerGetter = $entityManagerOrGetter;
		} else {
			throw new InvalidArgumentException('$entityManagerOrGetter must be a callable or an instance of EntityManagerInterface');
		}
	}


	function getEntityManager(): EntityManagerInterface {
		if ($this->entityManager) {
			return $this->entityManager;
		}
		if ($this->entityManagerGetter) {
			$getter = $this->entityManagerGetter;
			$manager = $getter();
			if (!$manager or !($manager instanceof EntityManagerInterface)) {
				throw new Exception('entityManagerGetter getter of ' . $this->entityId . ' did not return a EntityManagerInterface.');
			}
			$this->entityManager = $manager;
			$this->entityManagerGetter = null;
			return $this->entityManager;
		}
		throw new Exception('Neither Entity manager nor EntityManagerGetter is defined for entity ' . $this->entityId);
	}


}
