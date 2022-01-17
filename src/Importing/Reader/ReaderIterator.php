<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use Iterator;

class ReaderIterator implements Iterator {

	/**
	 * @var ReaderInterface
	 */
	protected $reader;

	/**
	 * @var mixed
	 */
	protected $currentRow = null;

	/**
	 * @var mixed
	 */
	protected $currentRowRead = false;

	/**
	 *
	 *
	 * @param ReaderInterface $reader
	 */
	function __construct(ReaderInterface $reader) {
		$this->reader = $reader;
	}
	
	public function current() {
		return $this->currentRow;
	}

	public function next() {
		$this->currentRowRead = false;
		$this->readRowIfNeeded();
	}

	public function key() {
		return $this->reader->getCurrentPosition();
	}

	public function valid() {
		$v = $this->currentRow !== null;
		if (!$v) {
			$this->reader->endReading();
		}
		return $v;
	}

	public function rewind() {
		$this->reader->startReading();
		$this->readRowIfNeeded();
	}

	protected function readRowIfNeeded() {
		if (!$this->currentRowRead) {
			$this->currentRow = $this->reader->readNextItem();
			$this->currentRowRead = true;
		}
	}

}
