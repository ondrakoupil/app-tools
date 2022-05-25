<?php

namespace OndraKoupil\AppTools\FileManager;

use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Exceptions\FileException;
use OndraKoupil\Tools\Files;
use OndraKoupil\Tools\Strings;
use Psr\Log\LoggerInterface;

class FileManager {

	/**
	 * @var string
	 */
	protected $pathToFilesDirectory;

	/**
	 * @var string
	 */
	protected $baseUrlOfFileDirectory;

	/**
	 * @var int
	 */
	protected $autoContextLevels;

	/**
	 * @var mixed[]
	 */
	protected $addedContext;

	/**
	 * @var LoggerInterface|null
	 */
	protected $logger;

	/**
	 *
	 *
	 * @param $pathToFilesDirectory
	 * @param $baseUrlOfFileDirectory
	 * @param LoggerInterface|null $logger
	 */
	function __construct($pathToFilesDirectory, $baseUrlOfFileDirectory, LoggerInterface $logger = null) {
		$this->pathToFilesDirectory = $pathToFilesDirectory;
		$this->baseUrlOfFileDirectory = $baseUrlOfFileDirectory;
		$this->logger = $logger;
	}

	/**
	 * @return string
	 */
	public function getPathToFilesDirectory(): string {
		return $this->pathToFilesDirectory;
	}

	/**
	 * @param int $autoContextLevels
	 */
	public function setAutoContextLevels(int $autoContextLevels): void {
		$this->autoContextLevels = $autoContextLevels;
	}

	/**
	 * @param string[]|string $addedContext
	 */
	public function setAddedContext($addedContext): void {
		$this->addedContext = Arrays::arrayize($addedContext);
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return string
	 */
	function getUrlOfFile(string $fileName, $context = array()): string {
		$fileName = $this->generateSafeFileName($fileName);
		return $this->mergePathParts(
			$this->baseUrlOfFileDirectory,
			$this->addedContext,
			$context,
			$this->calculateAutoContext($fileName),
			$fileName
		);
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return string
	 */
	function getPathOfFile(string $fileName, $context = array()): string {
		$fileName = $this->generateSafeFileName($fileName);
		return $this->mergePathParts(
			$this->pathToFilesDirectory,
			$this->addedContext,
			$context,
			$this->calculateAutoContext($fileName),
			$fileName
		);
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return string
	 */
	protected function getDirectoryPath(string $fileName, $context = array()): string {
		$fileName = $this->generateSafeFileName($fileName);
		return $this->mergePathParts(
			$this->pathToFilesDirectory,
			$this->addedContext,
			$context,
			$this->calculateAutoContext($fileName)
		);
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return bool
	 */
	function doesFileExist(string $fileName, $context = array()): bool {
		return file_exists($this->getPathOfFile($fileName, $context));
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return int
	 */
	function getFileSize(string $fileName, $context = array()): int {
		return @filesize($this->getPathOfFile($fileName, $context)) ?: 0;
	}

	/**
	 * @param string $fileName
	 * @param string|string[] $context
	 *
	 * @return int
	 */
	function getFileTime(string $fileName, $context = array()): int {
		return @filemtime($this->getPathOfFile($fileName, $context)) ?: 0;
	}

	/**
	 * @param string $filename
	 * @param string|string[] $context
	 *
	 * @return void
	 */
	function deleteFile(string $filename, $context = array()): void {
		$path = $this->getPathOfFile($filename, $context);
		try {
			Files::remove($path, true);
			$this->log('Deleted file', $filename, $context);
		} catch (FileException $e) {
			$this->logError('Failed deleting', $filename, $context);
			throw $e;
		}
	}

	/**
	 * @param string $oldFilename
	 * @param string $newFilename
	 * @param string|string[] $context
	 *
	 * @return void
	 */
	function renameFile(string $oldFilename, string $newFilename, $context = array()): void {
		if ($oldFilename === $newFilename) {
			return;
		}
		if ($this->doesFileExist($newFilename, $context)) {
			$this->logError('Failed renaming to ' . $newFilename . ', target file exists ', $oldFilename, $context);
			throw new FileException('File already exists: ' . $newFilename);
		}
		$ok = @rename($this->getPathOfFile($oldFilename, $context), $this->getPathOfFile($newFilename, $context));
		if (!$ok) {
			$this->logError('Failed renaming to ' . $newFilename, $oldFilename, $context);
			throw new FileException('File could not be renamed: ' . $oldFilename);
		} else {
			$this->log('Renamed to ' . $newFilename, $oldFilename, $context);
		}
	}

	/**
	 * @param string $preferredFilename
	 * @param string $content
	 * @param string|string[] $context
	 * @param bool $overwriteIfExists
	 *
	 * @return string
	 */
	function addFile(string $preferredFilename, string $content, $context = array(), bool $overwriteIfExists = false): string {

		if ($overwriteIfExists) {
			if ($this->doesFileExist($preferredFilename, $context)) {
				$this->deleteFile($preferredFilename, $context);
			}
		}

		$filename = $this->findFreeFilename($preferredFilename, $context);
		$this->writeIntoFile($content, $filename, $context, false);
		$this->log('Added file', $filename, $context);
		return $filename;

	}

	/**
	 * @param string $originalFilePath
	 * @param string $newFilename
	 * @param $newFileContext
	 * @param bool $overwriteIfExists
	 *
	 * @return string final new filename, differs from $newFilename only if $overwriteIfExists is false and $newFilename is already occupied
	 */
	function cloneFile(string $originalFilePath, string $newFilename, $newFileContext = array(), bool $overwriteIfExists = false): string {

		if (!file_exists($originalFilePath) or !is_readable($originalFilePath)) {
			throw new FileException('File not found: ' . $originalFilePath);
		}

		if (is_dir($originalFilePath)) {
			throw new FileException('Source file is a directory: ' . $originalFilePath);
		}

		if ($this->doesFileExist($newFilename, $newFileContext)) {
			if ($overwriteIfExists) {
				$this->deleteFile($newFilename, $newFileContext);
			} else {
				$newFilename = $this->findFreeFilename($newFilename, $newFileContext);
			}
		}

		$newFilePath = $this->getPathOfFile($newFilename, $newFileContext);

		$ok = copy($originalFilePath, $newFilePath);

		if (!$ok) {
			throw new FileException('Failed copying ' . $originalFilePath . ' to ' . $newFilePath);
		}

		Files::perms($newFilePath);

		return $newFilename;
	}

	/**
	 * @param string $filename
	 * @param string|string[] $context
	 *
	 * @return string
	 */
	function getFileContent(string $filename, $context = array()): string {
		return file_get_contents($this->getPathOfFile($filename, $context));
	}

	/**
	 * @param string $content
	 * @param string $filename
	 * @param string|string[] $context
	 * @param bool $append
	 *
	 * @return void
	 */
	function writeIntoFile(string $content, string $filename, $context = array(), bool $append = false): void {
		$filename = $this->generateSafeFileName($filename);
		$path = $this->getPathOfFile($filename, $context);
		if (!file_exists($path)) {
			$this->log('Created file with content length ' . strlen($content) . ' bytes', $filename, $context);
			Files::create($path, true, $content);
		} else {
			if ($append) {
				$this->log('Append content length ' . strlen($content) . ' bytes', $filename, $context);
				file_put_contents($path, $content, FILE_APPEND);
			} else {
				Files::remove($path);
				$this->log('Created file with content length ' . strlen($content) . ' bytes (overwritten old)', $filename, $context);
				Files::create($path, true, $content);
			}
		}
	}

	protected function generateSafeFileName(string $unsafeFileName): string {
		return Files::safeName($unsafeFileName);
	}

	protected function findFreeFilename(string $preferredFilename, $context = array()): string {

		$preferredFilename = $this->generateSafeFileName($preferredFilename);

		if (!$this->doesFileExist($preferredFilename, $context)) {
			return $preferredFilename;
		}

		$ext = Files::extension($preferredFilename, 'l');

		$basePart = Files::filenameWithoutExtension($preferredFilename);
		if (preg_match('~^(.+)-(\d{1,2})$~', $basePart, $matches)) {
			$basePart = $matches[1];
		}

		for ($i = 2; $i <= 99; $i++) {
			$tested = $basePart . '-' . $i;
			if ($ext) {
				$tested .= '.' . $ext;
			}
			if (!$this->doesFileExist($tested, $context)) {
				return $tested;
			}
		}

		$failsafe = Strings::randomString(16, true);
		if ($ext) {
			$failsafe .= '.' . $ext;
		}
		return $failsafe;
	}

	protected function mergePathParts(...$parts): string {
		$flat = array();
		foreach ($parts as $part) {
			foreach (Arrays::arrayize($part) as $part2) {
				if ($part2) {
					$flat[] = $part2;
				}
			}
		}
		return implode('/', $flat);
	}



	/**
	 * @param string $fileName
	 *
	 * @return int[]
	 */
	protected function calculateAutoContext(string $fileName): array {
		$md5 = md5($fileName);
		$ret = array();
		for ($i = 0; $i < $this->autoContextLevels; $i++) {
			$ret[] = $md5[$i];
		}
		return $ret;
	}

	protected function log($message, $filename, $context) {
		if ($this->logger) {
			$context = Arrays::arrayize($context);
			$message = $message . ' - filename ' . $filename . ', context [' . implode(', ', $context) . ']';
			$this->logger->info($message);
		}
	}

	protected function logError($err, $filename, $context) {
		if ($this->logger) {
			$context = Arrays::arrayize($context);
			$err = $err . ' - filename ' . $filename . ', context [' . implode(', ', $context) . ']';
			$this->logger->error($err);
		}
	}

}
