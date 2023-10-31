<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use Aspera\Spreadsheet\XLSX\Reader;
use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Strings;

class XlsxReader implements ReaderInterface {

	protected $filePath;

	/**
	 * @var Reader
	 */
	protected $readerInstance;

	protected $useLetters = false;
	protected $useNumbers = true;

	/**
	 * ${CARET}
	 *
	 * @param string $filePath
	 */
	public function __construct($filePath = '', $useLetters = false, $useNumbers = true) {
		$this->filePath = $filePath;
		$this->useLetters = $useLetters;
		$this->useNumbers = $useNumbers;
	}

	/**
	 * @param string $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	function setUsedIndices(bool $numbers, bool $letters) {
		$this->useNumbers = $numbers;
		$this->useLetters = $letters;
	}

	public function startReading() {
		$this->readerInstance = new Reader();
		$this->readerInstance->open($this->filePath);
	}

	public function readNextItem() {
		$this->readerInstance->next();
		if (!$this->readerInstance->valid()) {
			return null;
		}
		$curr = $this->readerInstance->current();

		if ($this->useLetters) {
			foreach ($curr as $number => $value) {
				$curr[Strings::numberToExcel($number)] = $value;
			}
		}
		if (!$this->useNumbers) {
			$curr = Arrays::removeNumericIndices($curr);
		}

		return $curr;
	}

	public function endReading() {
		$this->readerInstance->close();
	}

	public function getCurrentPosition() {
		return $this->readerInstance->key();
	}


}
