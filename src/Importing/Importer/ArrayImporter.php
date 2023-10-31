<?php

namespace OndraKoupil\AppTools\Importing\Importer;

use OndraKoupil\AppTools\Importing\Reader\ReaderInterface;
use OndraKoupil\AppTools\Importing\Reader\ReaderWithCallback;
use OndraKoupil\Tools\Strings;


/**
 * Reads a data source and loads it all to an array in memory
 */
class ArrayImporter implements ImporterInterface {

	/**
	 * @var ReaderInterface
	 */
	protected $reader;

	/**
	 * @var callable
	 */
	protected $transformCallback;

	/**
	 * @var callable
	 */
	protected $ignoreCallback;

	/**
	 * @var int
	 */
	protected $headerRowsCount;

	/**
	 * @var array
	 */
	protected $result;

	protected $colsMapping;

	protected $columnToIndex;

	protected $numberColumns;

	/**
	 * @param ReaderInterface $reader
	 */
	public function __construct(ReaderInterface $reader) {
		$this->setReader($reader);
	}

	/**
	 * @param ReaderInterface $reader
	 * @return void
	 */
	function setReader(ReaderInterface $reader) {
		$this->reader = $reader;
	}

	/**
	 * @param callable $callback function($row, $index) => $transformedRow
	 *
	 * @return void
	 */
	function setTransformCallback(callable $callback) {
		$this->transformCallback = $callback;
	}

	/**
	 * @param array $columns
	 *
	 * @return void
	 */
	function setNumericColumns(array $columns) {
		$this->numberColumns = $columns;
	}

	/**
	 * @param array $mappings
	 *
	 * Pole ve tvaru [Cislo_sloupce] => Klíč, pod kterým to bude ve výsledném poli
	 *
	 * @return void
	 */
	function setColumnsMapping(array $mappings) {
		$this->colsMapping = $mappings;
	}

	/**
	 * Ve výsledném poli budou položky indexovýny podle své hodnoty. Zadej např. "id" nebo tak něco.
	 *
	 * @param string $columnToIndex
	 *
	 * @return void
	 */
	function setIndexingByColumn(string $columnToIndex) {
		$this->columnToIndex = $columnToIndex;
	}

	/**
	 * Prvních $headerRowsCount bude přeskočeno. "1" = přeskočit 1 řádek a začít číst od 2. řádku
	 *
	 * @param int $headerRowsCount
	 */
	public function setHeaderRowsCount($headerRowsCount) {
		$this->headerRowsCount = $headerRowsCount;
	}

	/**
	 * @param callable $callback function ($row, $index) => vrátí-li true, bude řádek přeskočen
	 *
	 * @return void
	 */
	function setIgnoreCallback(callable $callback) {
		$this->ignoreCallback = $callback;
	}

	/**
	 * Nedělá nic.
	 *
	 * @param callable $callback
	 *
	 * @return void
	 */
	function setAfterSaveCallback(callable $callback) {
		return;
	}

	/**
	 * Vrací rovnou výsledek
	 *
	 * @return array
	 */
	function import() {
		$this->result = array();
		$callbackReader = new ReaderWithCallback(
			$this->reader,
			function($row, $index) {
				if ($this->headerRowsCount and $this->headerRowsCount >= $index) {
					return;
				}

				$i = $this->ignoreCallback;
				if ($i) {
					$ignore = $i($row);
					if ($ignore) {
						return;
					}
				}
				if ($this->colsMapping) {
					$row = ArrayImporter::applyColsMapping($row, $this->colsMapping);
				}
				if ($this->numberColumns) {
					foreach ($this->numberColumns as $numberColumn) {
						if (array_key_exists($numberColumn, $row)) {
							$row[$numberColumn] = Strings::number($row[$numberColumn], 0);
						}
					}
				}

				$c = $this->transformCallback;
				if ($c) {
					$row = $c($row, $index);
				}

				if (!$this->columnToIndex) {
					$this->result[] = $row;
				} else {
					$indexValue = $row[$this->columnToIndex] ?: 0;
					$this->result[$indexValue] = $row;
				}
			}
		);
		$callbackReader->read();
		return $this->result;
	}

	/**
	 * Vrací výsledek získaný z posledního běhu import()
	 *
	 *
	 * @return array
	 */
	function getResult() {
		return $this->result;
	}

	/**
	 * Vrací počet položek v posledním běhu import()
	 *
	 * @return int
	 */
	function getImportedItemsCount() {
		return $this->result ? count($this->result) : 0;
	}

	static protected function evalColumnNumberOrLetter($input) {
		if (!is_numeric($input)) {
			return Strings::excelToNumber($input);
		}
		return $input;
	}

	static protected function applyColsMapping($row, $mappings) {
		$out = array();
		foreach ($mappings as $index => $mappingName) {
			$index = self::evalColumnNumberOrLetter($index);
			$out[$mappingName] = $row[$index] ?? null;
		}
		return $out;
	}


}
