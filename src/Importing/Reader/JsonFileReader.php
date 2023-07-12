<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use OndraKoupil\Tools\Exceptions\FileException;
use OndraKoupil\Tools\Objects;
use RuntimeException;

/**
 * Reader for arrays in JSON in files
 */
class JsonFileReader implements ReaderInterface {

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $keyPath;

	protected $workingData;

	protected $positionInData = -1;

	/**
	 * ${CARET}
	 *
	 * @param string $file
	 */
	public function __construct(string $file, $keyPath = '') {
		$this->file = $file;
		$this->keyPath = $keyPath;
	}

	/**
	 * @param string $file
	 */
	public function setFile(string $file): void {
		$this->file = $file;
	}

	/**
	 * @param string $keyPath
	 */
	public function setKeyPath(string $keyPath): void {
		$this->keyPath = $keyPath;
	}


	public function startReading() {
		$readData = file_get_contents($this->file);
		if ($readData === false) {
			throw new FileException('File ' . $this->file . ' can not be read.');
		}
		$parsed = json_decode($readData, true);
		if ($parsed === null and json_last_error()) {
			throw new RuntimeException('File ' . $this->file . ' does not contain a JSON. ' . json_last_error_msg());
		}
		if ($this->keyPath) {
			$data = Objects::extractWithKeyPath($parsed, $this->keyPath);
		} else {
			$data = $parsed;
		}

		if (!is_array($data)) {
			throw new RuntimeException('File ' . $this->file . ' does not contain an iterable data. ');
		}

		$this->workingData = $data;

	}

	public function readNextItem() {
		$this->positionInData++;
		if (array_key_exists($this->positionInData, $this->workingData)) {
			return $this->workingData[$this->positionInData];
		}
		return null;
	}

	public function endReading() {
		$this->workingData = null;
	}

	public function getCurrentPosition() {
		return $this->positionInData;
	}


}
