<?php

namespace OndraKoupil\AppTools\Importing\Writer;

use OndraKoupil\Tools\Arrays;
use OndraKoupil\Tools\Strings;
use OndraKoupil\Tools\Time;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class ExcelWriter extends TableFileWriter {

	protected $mergedHeaderRows = array();

	protected $columnWidths = array();
	protected $columnFormats = array();
	protected $columnNumericFormats = array();
	protected $columnDateTimes = array();

	protected $headerStyleGlobal = null;
	protected $headerStyleForRow = array();

	protected $cellStyle = null;
	protected $columnStyle = array();
	protected $rowStyleCallback = null;

	protected $frozenRows = 0;
	protected $frozenColumns = 0;

	protected $sheetTitle = '';

	protected $properties;

	protected $currentRow = 0;

	protected $afterCallback;

	/**
	 * Šířka jednoho sloupce
	 *
	 * @param string|int $column
	 * @param int $width
	 *
	 * @return void
	 */
	function setColumnWidth($column, $width) {
		$this->columnWidths[self::normalizeColumn($column)] = $width;
	}

	/**
	 * Šířka vícero sloupců
	 *
	 * @param array $widths [kód-sloupce] => šířka
	 *
	 * @return void
	 */
	function setColumnWidths($widths) {
		foreach ($widths as $col => $width) {
			$this->setColumnWidth($col, $width);
		}
	}

	/**
	 * Přikotvení buněk. Alternativa k volání freezeHeader
	 *
	 * @param int $rows Počet přikotvených řádků
	 * @param int $cols Počet přikotvených sloupců
	 *
	 * @see freezeHeader()
	 *
	 * @return void
	 */
	function setFrozenCells($rows = 0, $cols = 0) {
		$this->frozenRows = $rows;
		$this->frozenColumns = $cols;
	}

	/**
	 * Zmrazí header. Alternativa k volání setFrozenCell.
	 *
	 * @param int $columns Volitelně umožní říct, kolik sloupců zleva má být přimraženo.
	 *
	 * @see setFrozenCells()
	 *
	 * @return void
	 */
	function freezeHeader($columns = 0) {
		$this->setFrozenCells(max(array_keys($this->headerRows)) + 1, $columns);
	}

	/**
	 * Sloučení buněk v záhlaví.
	 *
	 * @param int $fromCol Začáteční sloučená buňka
	 * @param int $toCol Konečná sloučená buňka
	 * @param int|null $row Volitelně - v jakém řádku záhlaví když ne v tom aktuálním?
	 *
	 * @return void
	 */
	function mergeHeaderCells($fromCol, $toCol, $row = null) {
		if ($row === null) {
			$row = $this->currentHeaderRow;
		}
		if (!isset($this->mergedHeaderRows[$row])) {
			$this->mergedHeaderRows[$row] = array();
		}
		$this->mergedHeaderRows[$row][] = array(self::normalizeColumn($fromCol), self::normalizeColumn($toCol));
	}

	/**
	 * @param callable $style function (Style $style, Worksheet $sheet, int $headerRowsCount, $maxColumnNum)
	 *
	 *
	 * @return void
	 */
	function setHeaderStyle(callable $style) {
		$this->headerStyleGlobal = $style;
	}

	/**
	 * @param callable $style function (Style $style, Worksheet $sheet,	$rowNumber,	$maxColumnNum)
	 *
	 * @param null|int $row
	 *
	 * @return void
	 */
	function setHeaderStyleForRow(callable $style, $row = null) {
		if ($row === null) {
			$row = $this->currentHeaderRow;
		}
		$this->headerStyleForRow[$row] = $style;
	}

	/**
	 * @param string $title
	 *
	 * @return void
	 */
	function setSheetTitle(string $title) {
		$this->sheetTitle = $title;
	}

	/**
	 * @param string $title
	 * @param string $creator
	 * @param string $description
	 * @param string $subject
	 * @param string $company
	 *
	 * @return void
	 */
	function setSpreadsheetProperties(
		string $title,
		string $creator,
		string $description,
		string $subject,
		string $company
	) {
		$this->properties = array(
			'title' => $title,
			'creator' => $creator,
			'description' => $description,
			'subject' => $subject,
			'company' => $company,
		);
	}

	/**
	 * @param callable $callback function(Worksheet $sheet, $lastRow, $firstDataRow, $columnsCount)
	 *
	 *   - Worksheet $sheet
	 *   - int $lastRow (or count of rows; in excel numbering, ie. 6 = 6th row is last one with data)
	 *   - int $firstDataRow (in excel numbering, ie. 3 = 2 rows of headers, 3rd row is first with data)
	 *   - int $columnsCount (ie. 4 = last column with data is D)
	 *
	 *
	 * @return void
	 */
	function setAfterCallback(callable $callback) {
		$this->afterCallback = $callback;
	}

	/**
	 * Manuální nastavení typu dat pro určitý sloupec
	 *
	 * @param array $columns [kód-sloupce] => DataType konstanta
	 *
	 * @return void
	 */
	function setColumnsDataType($columns) {
		foreach ($columns as $column => $format) {
			$this->setColumnDataType($column, $format);
		}
	}

	/**
	 * Manuální nastavení typu dat pro určitý sloupec
	 *
	 * @param string|int $column Kód sloupce
	 * @param mixed $dataFormat  DataType konstanta
	 *
	 * @return void
	 */
	function setColumnDataType($column, $dataFormat) {
		$this->columnFormats[self::normalizeColumn($column)] = $dataFormat;
	}

	/**
	 * Výchozí styl pro datové buňky
	 *
	 * @param callable $style function (Style $style, Worksheet $sheet, int $firstDataRow, int $numCols, int $numRows)
	 *
	 *
	 * @return void
	 */
	function setCellStyle(callable $style) {
		$this->cellStyle = $style;
	}

	/**
	 * Styl pro datové buňky v konkrétním sloupcu
	 *
	 * @param string|int $column Kód sloupce
	 * @param callable $style function (Style $style, Worksheet $sheet,	int $columnNumber, int $firstDataRow, int $totalRows)
	 *
	 * @return void
	 */
	function setColumnStyle($column, callable $style) {
		$this->columnStyle[self::normalizeColumn($column)] = $style;
	}

	/**
	 * Nastaví, že dané sloupečky jsou číselné
	 *
	 * @param int|string|array $columns Kód sloupce nebo vícero sloupců
	 * @param string|number $decimalsOrFormat Číslo = počet desetin. String = konkrétní datový formát pro Excel.
	 * @param bool $percentage True = zobrazovat jako procenta.
	 *
	 * @return void
	 */
	function setNumericColumns($columns, $decimalsOrFormat = 0, $percentage = false) {

		if (is_string($decimalsOrFormat)) {
			$format = $decimalsOrFormat;
		} else {
			$format = '0';
			if ($decimalsOrFormat) {
				$format .= '.' . str_repeat('0', $decimalsOrFormat);
			}
			if ($percentage) {
				$format .= '%';
			}
		}

		$columns = Arrays::arrayize($columns);
		foreach ($columns as $col) {
			$this->columnNumericFormats[self::normalizeColumn($col)] = $format;
			$this->columnFormats[self::normalizeColumn($col)] = DataType::TYPE_NUMERIC;
		}
	}

	/**
	 * Nastaví, že dané sloupečky jsou datum a/nebo čas
	 *
	 * @param int|string|array $columns Kód sloupce nebo vícero sloupců
	 * @param bool|string $formatOrShowTime True = zobraz jako datum a čas. False = zobraz jako datum bez času. String = konkrétní datový formát pro Excel.
	 *
	 * @return void
	 */
	function setDateTimeColumns($columns, $formatOrShowTime = true) {
		$columns = Arrays::arrayize($columns);
		foreach ($columns as $col) {
			if ($formatOrShowTime === true) {
				$format = NumberFormat::FORMAT_DATE_DATETIME;
			} elseif ($formatOrShowTime === false) {
				$format = NumberFormat::FORMAT_DATE_DDMMYYYY;
			} else {
				$format = $formatOrShowTime;
			}
			$this->columnDateTimes[self::normalizeColumn($col)] = $format;
		}
	}

	/**
	 * Nastaví, že dané sloupečky jsou bool
	 *
	 * @param int|string|array $columns Kód sloupce nebo vícero sloupců
	 *
	 * @return void
	 */
	function setBooleanColumns($columns) {
		$columns = Arrays::arrayize($columns);
		foreach ($columns as $col) {
			$this->columnFormats[self::normalizeColumn($col)] = DataType::TYPE_BOOL;
		}
	}

	/**
	 * Callback pro každý řádek.
	 *
	 * @param callable $rowStyleCallback function (Style $style, int $rowNumber, mixed $item, mixed $preparedItem)
	 *
	 *  - $style = Style objekt pro celý řádek
	 *  - $rowNumber = Číslo řádku (excelovské)
	 *  - $item = Původní item ze vstupu
	 *  - $preparedItem = Item se zapracovanými mappingy a dalším processingem
	 *
	 * @return void
	 */
	function setRowStyle(callable $rowStyleCallback) {
		$this->rowStyleCallback = $rowStyleCallback;
	}

	protected function createBlankSpreadsheet(): Spreadsheet {
		$spreadsheet = new Spreadsheet();

		if ($this->sheetTitle) {
			$spreadsheet->getActiveSheet()->setTitle($this->sheetTitle);
		}

		$props = $spreadsheet->getProperties();
		$props->setCreated(time());
		if ($this->properties['title'] ?? '') {
			$props->setTitle($this->properties['title']);
		}
		if ($this->properties['creator'] ?? '') {
			$props->setCreator($this->properties['creator']);
		}
		if ($this->properties['company'] ?? '') {
			$props->setCompany($this->properties['company']);
		}
		if ($this->properties['description'] ?? '') {
			$props->setDescription($this->properties['description']);
		}
		if ($this->properties['subject'] ?? '') {
			$props->setSubject($this->properties['subject']);
		}

		return $spreadsheet;
	}


	protected function applyColumnWidths(Worksheet $sheet) {
		foreach ($this->columnWidths as $columnIndex => $width) {
			if ($width) {
				$sheet->getColumnDimension(Strings::numberToExcel($columnIndex))->setWidth($width);
			}
		}
	}

	protected function writeHeadersToSheet(Worksheet $sheet) {

		foreach ($this->headerRows as $row => $headers) {
			$excelRow = $row + 1;
			foreach ($headers as $cellIndex => $headerName) {
				$excelCellIndex = $cellIndex + 1;
				$sheet->setCellValue(array($excelCellIndex, $excelRow), $headerName);
			}
			if ($this->mergedHeaderRows[$row] ?? null) {
				foreach ($this->mergedHeaderRows[$row] as $merge) {
					$from = $merge[0] + 1;
					$to = $merge[1] + 1;
					$sheet->mergeCells(array($from, $excelRow, $to, $excelRow));
				}
			}
		}

		$this->currentRow = max(array_keys($this->headerRows)) + 1;

	}

	protected function applyHeaderStyles(Worksheet $sheet) {
		$headerRowsCount = max(array_keys($this->headerRows)) + 1;
		$dims = $this->calculateSpreadsheetSize($sheet);
		$maxColumnNum = $dims['w'];
		if ($this->headerStyleGlobal) {
			$style = $sheet->getStyle(array(1, 1, $maxColumnNum, $headerRowsCount));
			call_user_func_array($this->headerStyleGlobal, array(
				$style,
				$sheet,
				$headerRowsCount,
				$maxColumnNum
			));
		}
		if ($this->headerStyleForRow) {
			foreach ($this->headerStyleForRow as $rowNumber => $styleFn) {
				if ($styleFn) {
					$style = $sheet->getStyle(array(1, $rowNumber + 1, $maxColumnNum, $rowNumber + 1));
					call_user_func_array($styleFn, array(
						$style,
						$sheet,
						$rowNumber + 1,
						$maxColumnNum
					));
				}
			}
		}
	}

	protected function writeDataRowToSheet(Worksheet $sheet, array $item) {
		$preparedItem = $this->prepareItem($item);
		if ($preparedItem === null) {
			return;
		}
		foreach ($preparedItem as $colIndex => $value) {
			if ($value === '') {
				$sheet->setCellValue(array($colIndex + 1, $this->currentRow + 1), $value);
			} elseif ($value === null) {
				$sheet->setCellValueExplicit(array($colIndex + 1, $this->currentRow + 1), null, DataType::TYPE_NULL);
			} else {

				if ($this->columnDateTimes[$colIndex] ?? false) {
					$val = Time::convert($value, Time::PHP);
					$excelTime = Date::PHPToExcel($val);
					$sheet->setCellValue(array($colIndex + 1, $this->currentRow + 1), $excelTime);
				} else {
					if ($this->columnFormats[$colIndex] ?? '') {
						$sheet->setCellValueExplicit(array($colIndex + 1, $this->currentRow + 1), $value, $this->columnFormats[$colIndex]);
					} else {
						$sheet->setCellValue(array($colIndex + 1, $this->currentRow + 1), $value);
					}
				}
			}
		}
		if ($this->rowStyleCallback) {
			$style = $sheet->getStyle(array(1, $this->currentRow + 1, count($preparedItem), $this->currentRow + 1));
			call_user_func_array($this->rowStyleCallback, array(
				$style,
				$this->currentRow + 1,
				$item,
				$preparedItem
			));
		}
		$this->currentRow++;
	}

	protected function calculateSpreadsheetSize(Worksheet $sheet) {
		$dimensions = $sheet->calculateWorksheetDimension();
		$range = Coordinate::splitRange($dimensions);
		$maxColumnCell = $range[0][1];
		$maxColumn = Coordinate::coordinateFromString($maxColumnCell)[0];
		$maxRow = Coordinate::coordinateFromString($maxColumnCell)[1];
		$maxColumnNum = Strings::excelToNumber($maxColumn, true);
		return array('w' => $maxColumnNum + 1, 'h' => $maxRow);
	}

	protected function finalizeSheet(Worksheet $sheet) {
		if ($this->frozenColumns or $this->frozenColumns) {
			$sheet->freezePane(array($this->frozenColumns + 1, $this->frozenRows + 1));
		}

		$dims = $this->calculateSpreadsheetSize($sheet);
		$firstDataRow = max(array_keys($this->headerRows)) + 2;

		if ($this->cellStyle) {
			$style = $sheet->getStyle(array(1, $firstDataRow, $dims['w'], $dims['h']));
			call_user_func_array($this->cellStyle, array(
				$style,
				$sheet,
				$firstDataRow,
				$dims['w'],
				$dims['h']
			));
		}

		foreach ($this->columnStyle as $col => $styleFn) {
			if ($style) {
				$style = $sheet->getStyle(array($col + 1, $firstDataRow, $col + 1, $dims['h']));
				call_user_func_array($styleFn, array(
					$style,
					$sheet,
					$col + 1,
					$firstDataRow,
					$dims['h']
				));
			}
		}

		foreach ($this->columnDateTimes as $col => $date) {
			if ($date) {
				$style = $sheet->getStyle(array($col + 1, $firstDataRow, $col + 1, $dims['h']));
				$style->getNumberFormat()->setFormatCode($date);
			}
		}

		foreach ($this->columnNumericFormats as $col => $numericFormat) {
			if ($numericFormat or $numericFormat === '0') {
				$style = $sheet->getStyle(array($col + 1, $firstDataRow, $col + 1, $dims['h']));
				$style->getNumberFormat()->setFormatCode($numericFormat);
			}
		}

		if ($this->afterCallback) {
			call_user_func_array(
				$this->afterCallback,
				array(
					$sheet,
					$this->currentRow,
					$firstDataRow,
					$dims['w']
				)
			);
		}

	}


}
