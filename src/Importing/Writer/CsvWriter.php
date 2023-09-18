<?php

namespace OndraKoupil\AppTools\Importing\Writer;

use RuntimeException;

class CsvWriter extends TableFileWriter {

	protected $separator = ';';
	protected $enclosure = '"';
	protected $headerSeparatorContent = null;

	protected $filePointer = null;

	protected $currentRow = 0;

	function setSeparator(string $separator): void {
		$this->separator = $separator;
	}

	function setEnclosure(string $enclosure): void {
		$this->enclosure = $enclosure;
	}

	function setHeaderSeparatorContent($content = null) {
		$this->headerSeparatorContent = $content;
	}


	function startWriting(): void {
		$this->filePointer = fopen($this->filePath, 'w');
		$this->currentRow = 0;
		$this->writeHeaders();
	}

	function endWriting(): void {
		fclose($this->filePointer);
	}

	function write($item): void {
		$item = $this->prepareItem($item);
		if ($item !== null) {
			$this->writeRow($item);
		}
	}

	function getCurrentPosition() {
		return $this->currentRow;
	}

	protected function writeRow(array $item) {
		fputcsv($this->filePointer, $item, $this->separator, $this->enclosure);
		$this->currentRow++;
	}

	protected function writeHeaders() {
		$headers = $this->prepareHeaders();
		foreach ($headers as $headerRow) {
			$this->writeRow($headerRow);
		}
	}

	protected function prepareHeaders() {
		$headers = array();
		$maxWidth = 0;
		foreach ($this->headerRows as $row) {
			if (!$row) {
				$w = 0;
			} else {
				$w = max(array_keys($row));
			}
			if ($w > $maxWidth) {
				$maxWidth = $w;
			}
		}
		foreach ($this->headerRows as $headerIndex => $row) {
			for ($i = 0; $i <= $maxWidth; $i++) {
				if (isset($row[$i])) {
					$headers[$headerIndex][$i] = $row[$i];
				} else {
					$headers[$headerIndex][$i] = '';
				}
			}
		}
		if ($this->headerSeparatorContent !== null) {
			$headerSeparator = array_fill(0, $maxWidth + 1, $this->headerSeparatorContent);
			$headers[] = $headerSeparator;
		}
		if ($this->headerCallback) {
			$headersReturned = call_user_func_array($this->headerCallback, array($headers));
			if ($headersReturned) {
				$headers = $headersReturned;
			}
		}
		return $headers;
	}


}
