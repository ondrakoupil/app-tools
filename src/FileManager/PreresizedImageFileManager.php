<?php

namespace OndraKoupil\AppTools\FileManager;

use InvalidArgumentException;
use OndraKoupil\AppTools\FileManager\Images\ImageDimensions;
use OndraKoupil\AppTools\FileManager\Images\ImageVersion;
use RuntimeException;

/**
 * Image manager that creates image versions in the moment of the image's upload.
 */
class PreresizedImageFileManager {

	const ORIGINAL = 'original';

	/**
	 * @var FileManager
	 */
	protected $fileManager;

	/**
	 * @var ImageVersion[]
	 */
	protected $versions = array();

	/**
	 * @var string
	 */
	protected $originalFileContext = '';

	/**
	 * @param FileManager $fileManager
	 * @param ImageVersion[] $versions
	 * @param string $originalFileContext
	 */
	function __construct(FileManager $fileManager, array $versions = array(), string $originalFileContext = self::ORIGINAL) {
		$this->fileManager = $fileManager;
		$this->originalFileContext = $originalFileContext;
		foreach ($versions as $version) {
			$this->addVersion($version);
		}
	}

	/**
	 * @return ImageVersion[]
	 */
	public function getVersions(): array {
		return $this->versions;
	}

	/**
	 * @return string
	 */
	public function getOriginalFileContext(): string {
		return $this->originalFileContext;
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
			$finalPath = $this->fileManager->getPathOfFile($finalFilename, $version->getId());
			self::applyTransformationsToFile($finalPath, $version);
		}
		return $finalFilename;
	}

	public function addImageFromResource($resource, string $filename, string $imageFormat, int $quality = 0): string {
		$originalContent = self::saveResourceToFile(null, $resource, $imageFormat, $quality);
		$finalFilename = $this->fileManager->addFile($filename, $originalContent, $this->originalFileContext);
		foreach ($this->versions as $version) {
			$resourceOfVersion = self::applyTransformationsToImageFromResource($resource, $version);
			$savedToFile = self::saveResourceToFile(null, $resourceOfVersion, $imageFormat, $quality);
			$this->fileManager->writeIntoFile($savedToFile, $finalFilename, $version->getId());
		}
		return $finalFilename;
	}

	public function deleteImage(string $filename): void {
		$this->fileManager->deleteFile($filename, $this->originalFileContext);
		foreach ($this->versions as $version) {
			$this->fileManager->deleteFile($filename, $version->getId());
		}
	}

	public function imageExists(string $filename): bool {
		return $this->fileManager->doesFileExist($filename, $this->originalFileContext);
	}

	public function getImageUrl(string $filename, string $version = '', bool $addTimestamps = false): string {
		if ($version === '') {
			$version = $this->getOriginalFileContext();
		}
		$url = $this->fileManager->getUrlOfFile($filename, $version);
		if ($addTimestamps) {
			$t = $this->fileManager->getFileTime($filename, $version);
			$url .= '?t=' . $t;
		}
		return $url;
	}


	public function getImageAllUrls(string $filename, bool $addTimestamps = false): array {
		$versions = array(
			$this->originalFileContext => $this->getOriginalUrl($filename, $addTimestamps)
		);
		foreach ($this->versions as $version) {
			$versionId = $version->getId();
			$versions[$versionId] = $this->getImageUrl($filename, $versionId, $addTimestamps);
		}
		return $versions;
	}

	public function getImageDimensions(string $filename, string $version = ''): ?ImageDimensions {
		$path = $this->getImagePath($filename, $version);
		$im = @getimagesize($path);
		if ($im) {
			return new ImageDimensions($im[0], $im[1]);
		} else {
			return null;
		}
	}

	public function getImagePath(string $filename, string $version): string {
		if ($version === '') {
			$version = $this->getOriginalFileContext();
		}
		return $this->fileManager->getPathOfFile($filename, $version);
	}

	public function getOriginalPath(string $filename): string {
		return $this->getImagePath($filename, $this->originalFileContext);
	}

	public function getOriginalUrl(string $filename, bool $addTimestamp = false): string {
		return $this->getImageUrl($filename, $this->originalFileContext);
	}

	public static function applyTransformationsToFile(string $filePath, ImageVersion $version, string $outputPath = ''): void {
		$res = self::applyTransformationsToImageFromFile($filePath, $version);
		if ($res) {
			self::saveResourceToFile($outputPath ?: $filePath, $res['resource'], $res['format'], $version->getQuality());
		}
	}

	/**
	 * @param resource $resource
	 * @param ImageVersion $version
	 *
	 * @return resource
	 */
	public static function applyTransformationsToImageFromResource($resource, ImageVersion $version) {
		$transformations = $version->getTransformations();
		foreach ($transformations as $transformation) {
			$resource = $transformation->transform($resource);
		}
		return $resource;
	}

	/**
	 * @param string $filePath
	 * @param ImageVersion $version
	 *
	 * @return null|array Null = SVG or other untransformable yet displayable image
	 * Else array([resource], [format])
	 */
	public static function applyTransformationsToImageFromFile(string $filePath, ImageVersion $version) {
		$loaded = self::loadFileToResource($filePath);
		if ($loaded) {
			$resource = $loaded['resource'];
			$resource = self::applyTransformationsToImageFromResource($resource, $version);
			return array('resource' => $resource, 'format' => $loaded['format']);
		}
		return null;
	}

	/**
	 * @param string $filePath
	 *
	 * @return null|array Null = SVG or other untransformable yet displayable image
	 * Else array([resource], [format])
	 */
	public static function loadFileToResource($filePath) {

		$imageFormat = mime_content_type($filePath);

		if ($imageFormat === 'image/svg+xml') {
			return null;
		}

		switch ($imageFormat) {
			case 'image/jpeg':
				$resource = imagecreatefromjpeg($filePath);
				break;

			case 'image/png':
				$resource = imagecreatefrompng($filePath);
				break;

			case 'image/webp':
				$resource = imagecreatefromwebp($filePath);
				break;

			default:
				throw new RuntimeException('Unknown format: ' . $imageFormat);
		}

		return array('resource' => $resource, 'format' => $imageFormat);

	}

	/**
	 * @param string|null $filePath Null = return as string
	 * @param resource $resource
	 * @param string $imageFormat
	 * @param null|int $quality
	 *
	 * @return void|string Returns string if $filePath is null
	 */
	public static function saveResourceToFile($filePath, $resource, $imageFormat, $quality = null) {

		if (!$filePath) {
			$filePath = null;
			ob_start();
		}

		switch ($imageFormat) {
			case 'image/jpeg':
				imagejpeg($resource, $filePath, $quality ?: 82);
				break;

			case 'image/png':
				imagepng($resource, $filePath);
				break;

			case 'image/webp':
				imagewebp($resource, $filePath, $quality ?: 82);
				break;

			default:
				if (!$filePath) {
					ob_end_clean();
				}
				throw new InvalidArgumentException('Unknown image format: ' . $imageFormat);
		}

		if (!$filePath) {
			return ob_get_clean();
		}
	}

}

