<?php

use OndraKoupil\AppTools\Importing\Writer\CsvWriter;
use OndraKoupil\AppTools\Importing\Writer\XlsxWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

include '../vendor/autoload.php';

$writer = new XlsxWriter(__DIR__ . '/../temp/output.xlsx');

$writer->setColumnHeaders(array('ID', 'Název', 'Cena', 'Počet', 'Je zralé?'));
$writer->addHeaderRow();
$writer->setColumnHeaders(array('XXX', 'BBB', '', '', 'ano/ne', 'Datum'));
$writer->mergeHeaderCells('B', 'D');
$writer->setSheetTitle('Tabulka lesního ovoce');

$writer->setColumnWidth('b', 40);
$writer->setColumnWidth('c', 25);
$writer->setColumnWidth('d', 25);
$writer->setColumnWidth('e', 16);
$writer->setColumnWidth('f', 40);

$writer->setHeaderStyle(function (Style $style) {
	$style->getBorders()->getInside()->setBorderStyle(Border::BORDER_THICK)->setColor(new Color(Color::COLOR_BLUE));
	$style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color(Color::COLOR_DARKRED));
	$style->getFont()->setColor(new Color(Color::COLOR_WHITE))->setBold(true);
	$style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
	$style->getFont()->setSize(18);
});

$writer->setHeaderStyleForRow(function (Style $style) {
	$style->getFont()->setColor(new Color(Color::COLOR_GREEN));
	$style->getFont()->setItalic(true);
});

$writer->setCellStyle(function (Style $style) {
	$style->getFont()->setSize(15);
});

$writer->setColumnStyle('B', function(Style $style) {
	$style->getFont()->setSize(12)->setItalic(true);
	$style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
	$style->getBorders()->getRight()->setBorderStyle(Border::BORDER_MEDIUM);
});

$writer->setColumnStyle('C', function(Style $style) {
	$style->getAlignment()->setIndent(2);
	$style->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_0);
});

$writer->setNumericColumns('C', 1);
$writer->setNumericColumns('D', 3, true);
$writer->setBooleanColumns('E');

$writer->freezeHeader(1);

//$writer->setAfterCallback(function () {
//	print_r(func_get_args());
//});

$writer->setColumnDataType('c', DataType::TYPE_NUMERIC);
$writer->setColumnDataType('d', DataType::TYPE_NUMERIC);
$writer->setColumnDataType('A', DataType::TYPE_STRING2);
$writer->setDateTimeColumns('F', false);

$writer->setMapping('A', 'id');
$writer->setMapping('B', 'name');
$writer->setMapping('c', 'price');
$writer->setMapping(3, 'count');
$writer->setMapping('e', 'zrale');
$writer->setMapping('f', 'date');

$writer->setRowStyle(function (Style $style, $rowNumber, $item) {
	if (($item['price'] ?? '') > 1000) {
		$style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color(Color::COLOR_YELLOW));
	}
});

$sumCount = 0;

$writer->setItemCallback(function ($item) use (&$sumCount) {
	$sumCount += ($item[3] ?? 0) ?: 0;
	return $item;
});

$writer->startWriting();

$writer->write(array(
	'id' => '100',
	'name' => 'Jahoda',
	'price' => 120.1234442,
	'zrale' => false,
));

$writer->write(array(
	'id' => '120a',
	'name' => 'Malina',
	'price' => 1573,
	'count' => 10,
	'zrale' => true,
	'date' => time() - 864000,
));

$writer->write(array(
	'id' => 'x14g0',
	'name' => 'Borůvka',
	'D' => 11,
	'zrale' => true,
	'date' => '2023-01-04 18:02:03'
));

$writer->write(array(
	'150',
	'Ostružina',
	4000,
	20,
	'zrale' => false,
	'date' => new DateTime('now - 3 days'),
));

$writer->endWriting();

