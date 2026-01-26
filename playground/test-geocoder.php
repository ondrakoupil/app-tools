<?php

use OndraKoupil\AppTools\Importing\Writer\CsvWriter;
use OndraKoupil\AppTools\Importing\Writer\XlsxWriter;
use OndraKoupil\AppTools\Maps\Coords;
use OndraKoupil\AppTools\Maps\MapyCzGeocoder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

include '../vendor/autoload.php';

$geocoder = new MapyCzGeocoder('4VYJS2AFXpG9dQXf0LY4dM4LLvZV1Nc-pkhzkBOaTVo');

$res = $geocoder->geocodeFromAddress('Šafaříkova 717/20, Hradec Králové');

print_r($res);


$res2 = $geocoder->geocodeFromCoords(new Coords(50.27966, 15.32431));

print_r($res2);
