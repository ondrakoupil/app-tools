<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use OndraKoupil\AppTools\FileManager\FileManager;
use Psr\Log\LoggerInterface;

class FilePurgerWithManager extends FilePurger {

	/**
	 * @var FileManager
	 */
	private $manager;

	/**
	 * @param FileManager $manager
	 * @param ActionInterface $action
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(FileManager $manager, ActionInterface $action, LoggerInterface $logger = null) {
		parent::__construct($manager->getPathToFilesDirectory(), $action, $logger);
		$this->manager = $manager;
	}

	/**
	 *
	 *
	 * @param array $allowedFileNames
	 * @param array $allowedContexts
	 *
	 * @return array
	 */
	public function runWithManager(array $allowedFileNames, array $allowedContexts = array()): array {

		$allowedFiles = array();
		foreach ($allowedFileNames as $fileName) {
			if (!$allowedContexts) {
				$allowedFiles[] = $this->manager->getPathOfFile($fileName, '');
			} else {
				foreach ($allowedContexts as $context) {
					$path = $this->manager->getPathOfFile($fileName, $context);
					$allowedFiles[] = $path;
				}
			}
		}

		return parent::run($allowedFiles);

	}

}
