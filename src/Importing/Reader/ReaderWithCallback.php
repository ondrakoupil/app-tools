<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use Iterator;

class ReaderWithCallback {

	/**
	 * @var callable
	 */
	protected $callback;

	/**
	 * @var ReaderInterface
	 */
	private $reader;

	/**
	 * @param ReaderInterface $reader
	 * @param callable $callback function($row, $index)
	 */
	function __construct(ReaderInterface $reader, $callback) {
		$this->callback = $callback;
		$this->reader = $reader;
	}

	/**
	 * @return void
	 */
	function read() {
		$this->reader->startReading();
		$c = $this->callback;
		while (true) {
			$item = $this->reader->readNextItem();
			if ($item === null) {
				break;
			}
			$c($item, $this->reader->getCurrentPosition());
		}
		$this->reader->endReading();
	}


}
