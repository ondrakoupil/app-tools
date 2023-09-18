<?php

use OndraKoupil\AppTools\Importing\Writer\CsvWriter;

include '../vendor/autoload.php';

$writer = new CsvWriter(__DIR__ . '/../temp/output.csv');

$writer->setColumnHeaders(array('', '', '', '', '2022', '', '', '', '2023'));
$writer->addHeaderRow();
$writer->setColumnHeaders(array('ID', 'Název', 'Latinský název', 'Počet', '1. kvartál', '2. kvartál', '3. kvartál', '4. kvartál', '1. kvartál', '2. kvartál', '3. kvartál', '4. kvartál'));
$writer->setMappings(array(
	'A' => 'id',
	'B' => 'name',
	'D' => 'count',
));
$writer->setMapping(2, 'latin');
$writer->setHeaderSeparatorContent('-----');

$writer->setHeaderCallback(function ($headers) {
	echo "\nHeaders:";
	print_r($headers);
});

$writer->setItemCallback(function ($item) {
	echo "\nItem:";
	print_r($item);
	return $item;
});

$writer->startWriting();
$writer->write(array(
	'id' => '100',
	'name' => 'Borovice',
	'latin' => 'Pinus',
	3 => '100',
	20,
	30,
	40,
	50,
	60,
	70,
	80,
	90
));

$writer->write(array(
	'id' => '120',
	'name' => 'Pinie',
	'count' => '300',
	8 => '300'
));

$writer->write(array(
	'count' => '8222',
	10 => '???'
));

$writer->endWriting();
