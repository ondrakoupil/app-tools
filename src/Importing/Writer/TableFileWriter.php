<?php

namespace OndraKoupil\AppTools\Importing\Writer;

use Exception;
use InvalidArgumentException;
use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Strings;
use RuntimeException;

abstract class TableFileWriter implements WriterInterface {

	protected $headerRows = array();

	protected $currentHeaderRow = 0;

	protected $headerCallback = null;

	protected $mappings = array();

	protected $itemCallback = null;

	function __construct(
		protected $filePath
	) {

	}

	function setColumnHeader($column, $name, $row = null) {
		if ($row === null) {
			$row = $this->currentHeaderRow;
		}
		if (!isset($this->headerRows[$row])) {
			$this->headerRows[$row] = array();
		}
		$this->headerRows[$row][self::normalizeColumn($column)] = $name;
	}

	function setItemCallback(callable $callback) {
		$this->itemCallback = $callback;
	}

	function setColumnHeaders($headers, $row = null) {
		foreach ($headers as $col => $header) {
			$this->setColumnHeader($col, $header, $row);
		}
	}

	function setMappings($mappings) {
		foreach ($mappings as $column => $alias) {
			$this->setMapping($column, $alias);
		}
	}

	function setLetterColumnMappings($allowedLetterColumns) {
		foreach ($allowedLetterColumns as $column) {
			$this->setMapping($column, $column);
		}
	}

	function setLetterColumnRangeMapping($minLetter, $maxLetter, $uppercase = true) {
		$min = Strings::excelToNumber($minLetter);
		$max = Strings::excelToNumber($maxLetter);
		if ($max < $min) {
			throw new InvalidArgumentException($minLetter . ' must not be more than ' . $maxLetter);
		}
		for ($i = $min; $i <= $max; $i++) {
			$letter = Strings::numberToExcel($i, true, $uppercase);
			$this->setMapping($letter, $letter);
		}
	}

	function processMappingsInItem($item) {
		$processedItem = $item;
		$processedItem = Arrays::removeNonNumericIndices($processedItem);
		foreach ($this->mappings as $alias => $colNumber) {
			if (array_key_exists($alias, $item)) {
				$processedItem[$colNumber] = $item[$alias];
			}
		}
		return $processedItem;
	}

	function prepareItem(array $item) {
		$item = $this->processMappingsInItem($item);
		$w = $item ? max(array_keys($item)) : 0;
		for ($i = 0; $i < $w; $i++) {
			if (!array_key_exists($i, $item)) {
				$item[$i] = '';
			}
		}
		ksort($item);
		if ($this->itemCallback) {
			$item = call_user_func_array($this->itemCallback, array($item));
			if ($item === null or $item === false) {
				return null;
			}
			if (!is_array($item)) {
				throw new RuntimeException('ItemCallback must return an array or null.');
			}
		}

		return $item;
	}

	function setMapping($column, $alias) {
		$column = self::normalizeColumn($column);
		$this->mappings[$alias] = $column;
	}

	function addHeaderRow() {
		$this->currentHeaderRow++;
		if (!isset($this->headerRows[$this->currentHeaderRow])) {
			$this->headerRows[$this->currentHeaderRow] = array();
		}
	}

	function setHeaderCallback(callable $callback) {
		$this->headerCallback = $callback;
	}

	static function normalizeColumn($col) {
		if (is_numeric($col)) {
			return $col;
		}
		return Strings::excelToNumber($col);
	}

}
