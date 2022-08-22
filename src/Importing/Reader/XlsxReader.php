<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use Aspera\Spreadsheet\XLSX\Reader;

class XlsxReader implements ReaderInterface {

	protected $filePath;

	/**
	 * @var Reader
	 */
	protected $readerInstance;

	/**
	 * ${CARET}
	 *
	 * @param string $filePath
	 */
	public function __construct($filePath = '') {
		$this->filePath = $filePath;
	}

	/**
	 * @param string $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
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
		return $this->readerInstance->current();
	}

	public function endReading() {
		$this->readerInstance->close();
	}

	public function getCurrentPosition() {
		return $this->readerInstance->key();
	}


}
