<?php

namespace OndraKoupil\AppTools\Importing\Writer;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxWriter extends ExcelWriter {

	protected Spreadsheet $theSpreadsheet;
	protected Worksheet $theSheet;

	function startWriting(): void {
		$this->theSpreadsheet = $this->createBlankSpreadsheet();
		$sheet = $this->theSpreadsheet->getActiveSheet();
		$this->theSheet = $sheet;
		$this->writeHeadersToSheet($sheet);
		$this->applyColumnWidths($sheet);
		$this->applyHeaderStyles($sheet);

	}

	function endWriting(): void {
		$this->finalizeSheet($this->theSheet);
		$writer = new Xlsx($this->theSpreadsheet);
		$writer->save($this->filePath);
	}

	function write($item): void {
		$this->writeDataRowToSheet($this->theSheet, $item);
	}

	function getCurrentPosition() {
		return $this->currentRow;
	}


}
