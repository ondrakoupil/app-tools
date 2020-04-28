<?php

namespace OndraKoupil\AppTools\Importing\Reader;

use InvalidArgumentException;
use OndraKoupil\Tools\Arrays;
use RuntimeException;

/**
 *
 * ```php
 * $reader = new CsvReader('file.csv');
 *
 * // do some settings
 *
 * $reader->startReading();
 *
 * while ($row = $reader->readNextItem()) {
 *   doSomethingWithReadData($row);
 *   $reader->getCurrentRowNumber();
 * }
 *
 * $reader->endReading();
 * ```
 *
 *
 */
class CsvReader implements ReaderInterface {

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $delimiter = ';';

	/**
	 * @var string
	 */
	protected $enclosure = '"';

	/**
	 * @var int fopen handle
	 */
	protected $fileHandle;

	/**
	 * @var array [columnIndex] => columnAlias (0-based indexing)
	 */
	protected $columns = array();

	/**
	 * @var array [columnIndex] => true (0-based indexing)
	 */
	protected $columnCarryFromPrevious = array();

	/**
	 * @var int
	 */
	protected $skipRows = 0;

	/**
	 * @var callable function($row, $rowNumber) => boolean. False = přeskočit tuto řádku
	 */
	protected $validateCallback;

	/**
	 * Předchozí řádek
	 * @internal
	 * @var array
	 */
	protected $previousRow = null;

	/**
	 * @var int
	 */
	protected $rowNumber = 0;

	/**
	 * @param string $filename
	 * @param string $delimiter
	 * @param string $enclosure
	 */
	public function __construct($filename, $delimiter = null, $enclosure = null) {
		$this->setFile($filename);
	}

	/**
	 * @param string $file
	 */
	public function setFile($file) {
		if (file_exists($file) and is_readable($file)) {
			$this->file = $file;
		} else {
			throw new RuntimeException('File not readable: ' . $file);
		}
	}

	/**
	 * @param string $delimiter
	 */
	public function setDelimiter($delimiter) {
		if (strlen($delimiter) !== 1) {
			throw new InvalidArgumentException('Delimiter must be a single character.');
		}
		$this->delimiter = $delimiter;
	}

	/**
	 * @param string $enclosure
	 */
	public function setEnclosure($enclosure) {
		if (strlen($enclosure) !== 1) {
			throw new InvalidArgumentException('Enclosure must be a single character.');
		}
		$this->enclosure = $enclosure;
	}

	/**
	 * Kolik řádků se má přeskočit od začátku?
	 * 0 = nic nepřeskakovat, 1 = přeskočit první řádek (s indexem 0)
	 * @param int $skipRows
	 */
	public function setSkipRows($skipRows) {
		$this->skipRows = $skipRows;
	}


	/**
	 * Definuje jeden sloupeček
	 *
	 * @param int $columnIndex 0-based číslo sloupce anebo excel-like písmeno sloupce
	 * @param string $columnAlias Alias
	 *
	 * @return void
	 */
	public function defineColumn($columnIndex, $columnAlias) {
		if (!is_numeric($columnIndex)) {
			$columnIndex = self::excelLetterToNumber($columnIndex);
		}
		$this->columns[$columnIndex] = $columnAlias;
	}

	/**
	 * Definuje více sloupečků naráz
	 *
	 * @param array $columnDefinition [$columnIndex] => $columnAlias. 0-based indexy nebo excel-like čísla sloupců.
	 *
	 * @return void
	 */
	public function defineColumns($columnDefinition) {
		foreach ($columnDefinition as $colIndex => $colAlias) {
			$this->defineColumn($colIndex, $colAlias);
		}
	}

	/**
	 * @return void
	 */
	public function clearDefinedColumns() {
		$this->columns = array();
	}


	/**
	 * @param string|string[] $columnIds Jeden sloupec anebo array sloupců. Sloupec = buď 0-based index anebo definovaný alais.
	 *
	 * @return void
	 */
	public function enableCarryForColumns($columnIds) {
		$columnIds = Arrays::arrayize($columnIds);
		foreach ($columnIds as $columnId) {
			if (is_numeric($columnId)) {
				$this->columnCarryFromPrevious[$columnId] = true;
			} else {
				$columnIndex = array_search($columnId, $this->columns, false);
				if ($columnIndex !== false) {
					$this->columnCarryFromPrevious[$columnIndex] = true;
				} else {
					throw new InvalidArgumentException('Unknown column: ' . $columnId);
				}
			}
		}
	}


	/**
	 * @param callable $validateCallback function($row, $rowNumber) => boolean. False = přeskočit tuto řádku
	 */
	public function setValidateCallback($validateCallback) {
		$this->validateCallback = $validateCallback;
	}

	/**
	 * @param array $row
	 * @param int $rowNumber
	 *
	 * @return bool
	 */
	protected function validateRow($row, $rowNumber = 0) {
		if ($this->skipRows > 0 and $rowNumber < $this->skipRows) {
			return false;
		}
		if ($this->validateCallback) {
			$out = call_user_func_array($this->validateCallback, array($row, $rowNumber));
			if ($out === false) {
				return false;
			}
		}
		return true;
	}


	protected function processReadRow($rawRow, $previousRow = null) {
		foreach ($this->columnCarryFromPrevious as $columnIndex => $doCarry) {
			if ($doCarry) {
				if (!isset($rawRow[$columnIndex]) or $rawRow[$columnIndex] === '') {
					if (isset($previousRow[$columnIndex])) {
						$rawRow[$columnIndex] = $previousRow[$columnIndex];
					}
				}
			}
		}
		foreach ($this->columns as $columnIndex => $columnAlias) {
			$rawRow[$columnAlias] = isset($rawRow[$columnIndex]) ? $rawRow[$columnIndex] : '';
		}
		return $rawRow;
	}


	/**
	 * Zahájí čtení
	 *
	 * @return void
	 */
	public function startReading() {
		$this->fileHandle = fopen($this->file, 'r');
		$this->previousRow = null;
		$this->rowNumber = 0;
	}

	/**
	 * V průběhu lze získat aktuální číslo řádk (od 0) pomocí getCurrentRow
	 *
	 * @return array|null
	 */
	public function readNextItem() {
		$row = null;
		do {
			$read = fgetcsv($this->fileHandle, 0, $this->delimiter, $this->enclosure);
			if (!$read) {
				break;
			}
			$read = $this->processReadRow($read, $this->previousRow);
			$valid = $this->validateRow($read, $this->rowNumber);
			if ($valid) {
				$row = $read;
				$this->previousRow = $row;
			}
			$this->rowNumber++;
		} while (!$row);

		if ($row) {
			return $row;
		} else {
			return null;
		}

	}

	public function getCurrentRowNumber() {
		return $this->rowNumber - 1;
	}

	/**
	 * Ukončení čtení
	 *
	 * @return void
	 */
	public function endReading() {
		if ($this->fileHandle) {
			fclose($this->fileHandle);
			$this->fileHandle = null;
		}
	}

	public function getCurrentPosition() {
		return $this->getCurrentRowNumber();
	}

	/**
	 * @param string $letter
	 *
	 * @return int
	 */
	static function excelLetterToNumber($letter) {
		$excelSloupec=strtolower($letter);
		$cislo=0;
		while ($excelSloupec) {
			$pismenko = $excelSloupec[0];
			$cislo *= 26;
			$cislo += ord($pismenko) - 96;
			$excelSloupec = substr($excelSloupec, 1);
		}
		return $cislo-1;
	}


}
