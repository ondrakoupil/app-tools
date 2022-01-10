<?php

namespace OndraKoupil\AppTools\FileManager;

use InvalidArgumentException;
use OndraKoupil\AppTools\FileManager\Images\ImageVersion;

class PreresizedImageFileManager {

	const ORIGINAL = 'original';

	protected FileManager $fileManager;

	/**
	 * @var ImageVersion[]
	 */
	protected array $versions = array();

	protected string $originalFileContext = '';

	/**
	 * @param FileManager $fileManager
	 */
	function __construct(FileManager $fileManager, array $versions = array(), string $originalFileContext = self::ORIGINAL) {
		$this->fileManager = $fileManager;
		$this->originalFileContext = $originalFileContext;
		foreach ($versions as $version) {
			$this->addVersion($version);
		}
	}

	function addVersion(ImageVersion $version): self {
		if (isset($this->versions[$version->getId()])) {
			throw new InvalidArgumentException('Version is already defined: ' . $version->getId());
		}
		if ($this->originalFileContext === $version->getId()) {
			throw new InvalidArgumentException('This is already reserved for original context ' . $version->getId());
		}
		$this->versions[$version->getId()] = $version;
		return $this;
	}

	/**
	 * @return FileManager
	 */
	public function getFileManager(): FileManager {
		return $this->fileManager;
	}

	public function addImage(string $content, string $filename): string {
		$finalFilename = $this->fileManager->addFile($filename, $content, $this->originalFileContext);
		foreach ($this->versions as $version) {
			$this->fileManager->writeIntoFile($content, $finalFilename, $version->getId());
			$version->getTransformation()->transform($this->fileManager->getPathOfFile($finalFilename, $version->getId()));
		}
		return $finalFilename;
	}

	public function deleteImage(string $filename): void {
		$this->fileManager->deleteFile($filename, $this->originalFileContext);
		foreach ($this->versions as $version) {
			$this->fileManager->deleteFile($filename, $version->getId());
		}
	}

	public function getImageUrl(string $filename, string $version): string {
		return $this->fileManager->getUrlOfFile($filename, $version);
	}

	public function getImagePath(string $filename, string $version): string {
		return $this->fileManager->getPathOfFile($filename, $version);
	}

	public function getOriginalPath(string $filename): string {
		return $this->getImagePath($filename, $this->originalFileContext);
	}

	public function getOriginalUrl(string $filename): string {
		return $this->getImageUrl($filename, $this->originalFileContext);
	}



}
