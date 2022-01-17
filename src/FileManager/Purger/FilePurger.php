<?php

namespace OndraKoupil\AppTools\FileManager\Purger;

use Exception;
use OndraKoupil\AppTools\FileManager\FileManager;
use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Files;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Keep your files directory clean by purging unnecessary files and subdirectories.
 */
class FilePurger {

	/**
	 * @var string
	 */
	protected $directory;

	/**
	 * @var callable
	 */
	protected $exceptionCallback = null;

	/**
	 * @var string[]
	 */
	protected $exceptionFilePaths = array();

	/**
	 * @var string[]
	 */
	protected $exceptionFileNames = array();

	/**
	 * @var LoggerInterface|null
	 */
	protected $logger;

	/**
	 * @var int
	 */
	protected $minAgeInHours = 0;

	/**
	 * @var ActionInterface
	 */
	protected $action;

	/**
	 * ${CARET}
	 *
	 * @param string $directory Files directory to purge within
	 * @param ActionInterface $action What to do with unnecessary files? Use DryRunAction to no-op.
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(string $directory, ActionInterface $action, LoggerInterface $logger = null) {
		$this->directory = $directory;
		$this->logger = $logger;
		$this->action = $action;
	}

	/**
	 * Purge only unnecessary files older than X hours
	 *
	 * @param int $minAgeInHours
	 */
	public function setMinAgeInHours(int $minAgeInHours): void {
		$this->minAgeInHours = $minAgeInHours;
	}

	/**
	 * Callable that is evaluated on each suspicious file. If returns true, then it is considered to be an exception
	 * and not purged.
	 *
	 * @param callable $exceptionCallback function(string $file): bool
	 */
	public function setExceptionCallback(callable $exceptionCallback): void {
		$this->exceptionCallback = $exceptionCallback;
	}

	/**
	 * Exact file path (with subdirs)
	 *
	 * @param string $filePath
	 * @return void
	 */
	public function addExceptionFilePath(string $filePath): void {
		$this->exceptionFilePaths[$filePath] = true;
	}

	/**
	 * Filenames (without paths)
	 *
	 * @param string|string[] $fileNames
	 * @return void
	 */
	public function addExceptionFileName($fileNames): void {
		foreach (Arrays::arrayize($fileNames) as $fileName) {
			$this->exceptionFileNames[$fileName] = true;
		}
	}

	/**
	 * @param string[] $allowedFiles List of file paths that are necessary
	 *
	 * @return string[]
	 */
	public function run(array $allowedFiles): array {

		$purgableFiles = $this->findPurgableFiles($allowedFiles);

		$result = $this->action->startup();
		if ($this->logger and $result) {
			$this->logger->info($result);
		}

		foreach ($purgableFiles as $purgableFile) {
			$this->processFile($purgableFile);
		}

		$result = $this->action->cleanup($purgableFiles);
		if ($this->logger and $result) {
			$this->logger->info($result);
		}


		return $purgableFiles;
	}


	/**
	 * Delete subdirectories in this FilePurger's directory
	 * @return string[] Deleted directories
	 */
	public function deleteEmptyDirectories(): array {
		return $this->deleteEmptyDirectoryRecursion($this->directory, 0, true);
	}


	protected function processFile($file) {
		try {
			$res = $this->action->processFile($file);
			if ($this->logger) {
				$this->logger->info('Purged file ' . $file . ' - ' . $res);
			}
		} catch (Exception $e) {
			if ($this->logger) {
				$this->logger->error('Error at file ' . $file . ' - error ' . $e->getMessage());
			}
		}
	}


	/**
	 * @param string[] $allowedFiles
	 * @return string[]
	 */
	protected function findPurgableFiles(array $allowedFiles): array {
		$foundFiles = $this->findAllFiles();

		$potentialFiles = $this->filterByExceptions($this->filterByAge($foundFiles));

		$allowedFilesInverted = array_fill_keys($allowedFiles, true);

		$purgableFiles = array();

		foreach ($potentialFiles as $potentialFile) {
			if (!isset($allowedFilesInverted[$potentialFile])) {
				$purgableFiles[] = $potentialFile;
			}
		}

		return $purgableFiles;
	}


	/**
	 * Recursively find all files in a directory a nd its subdirectories
	 *
	 * @param string $directory
	 *
	 * @return string[]
	 */
	static function findAllFilesInDirectoryRecursive(string $directory): array {
		$dir = new RecursiveDirectoryIterator($directory);
		$iterator = new RecursiveIteratorIterator($dir);
		$files = array();
		/** @var SplFileInfo $file */
		foreach ($iterator as $file) {
			if ($file->isFile()) {
				$files[] = $file->getPathname();
			}
		}
		return $files;
	}

	/**
	 * @return string[]
	 */
	protected function findAllFiles(): array {
		return self::findAllFilesInDirectoryRecursive($this->directory);
	}

	/**
	 * @param string[] $files
	 * @return string[]
	 */
	protected function filterByAge(array $files): array {
		if (!$this->minAgeInHours) {
			return $files;
		}
		$now = time();
		return array_values(array_filter($files, function($file) use ($now) {
			$age = filemtime($file);
			if ($now - $age > $this->minAgeInHours * 3600) {
				return true;
			}
			return false;
		}));
	}

	/**
	 * @param string[] $files
	 * @return string[]
	 */
	protected function filterByExceptions(array $files): array {
		$exceptionCallback = $this->exceptionCallback;
		return array_values(array_filter($files, function($file) use ($exceptionCallback) {

			if ($exceptionCallback) {
				$ret = $exceptionCallback($file);
				if ($ret === true) {
					return false;
				}
			}

			if (isset($this->exceptionFilePaths[$file])) {
				return false;
			}


			$baseName = Files::filename($file);
			if (isset($this->exceptionFileNames[$baseName])) {
				return false;
			}

			return true;
		}));
	}


	/**
	 * @param string $directory
	 * @param int $failSafe
	 * @param bool $notThisOne
	 *
	 * @return string[]
	 */
	protected function deleteEmptyDirectoryRecursion(string $directory, int $failSafe, bool $notThisOne): array {
		if ($failSafe > 20) {
			return array();
		}
		$subFiles = glob($directory . '/*');
		$keptSubFiles = 0;
		$deleted = array();
		foreach ($subFiles as $subFile) {
			if (is_dir($subFile)) {
				$deletedSubs = $this->deleteEmptyDirectoryRecursion($subFile, $failSafe + 1, false);
				if ($deletedSubs) {
					$deleted = array_merge($deleted, $deletedSubs);
				}
				if (file_exists($subFile)) {
					$keptSubFiles++;
				}
			} else {
				$keptSubFiles++;
			}
		}
		if (!$keptSubFiles and !$notThisOne) {
			$this->logger->info('Deleting empty subdirectory ' . $directory);
			$ok = @rmdir($directory);
			if (!$ok) {
				$this->logger->error('Can not delete empty subdirectory ' . $directory);
			} else {
				$deleted[] = $directory;
			}
		}

		return $deleted;

	}



}
