<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use OndraKoupil\AppTools\FileManager\FileManager;
use OndraKoupil\AppTools\FileManager\PreresizedImageFileManager;
use Psr\Log\LoggerInterface;

class FilePurgerWithPreresizedImages extends FilePurgerWithManager {

	/**
	 * @var PreresizedImageFileManager
	 */
	protected $imageManager;

	public function __construct(PreresizedImageFileManager $imageFileManager, ActionInterface $action, LoggerInterface $logger = null) {
		parent::__construct($imageFileManager->getFileManager(), $action, $logger);
		$this->imageManager = $imageFileManager;
	}

	public function runWithImages(array $allowedFileNames): array {

		$allowedContexts = array();

		$originalContext = $this->imageManager->getOriginalFileContext();
		if ($originalContext) {
			$allowedContexts[] = $originalContext;
		}

		foreach ($this->imageManager->getVersions() as $version) {
			$allowedContexts[] = $version->getId();
		}

		return parent::runWithManager($allowedFileNames, $allowedContexts);

	}

}
