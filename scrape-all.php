<?php

use Samwilson\WaBmdScraper\PioneersIndexScraper;

require __DIR__ . '/vendor/autoload.php';

// Setup
$pioneersIndex = new PioneersIndexScraper();
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir);
}

// The main BMD loop.
foreach ($pioneersIndex->getAllowedTypes() as $type) {

    $pioneersIndex->init($type);
    $file = fopen(__DIR__ . "data/$type.csv", 'w');

    // First page
    $page1 = $pioneersIndex->getPage1();
    writeData($file, $page1);

    // Remaining pages
    $pageN = $pioneersIndex->getPageN();
    while (count($pageN) > 0) {
        writeData($file, $pageN);
        $pageN = $pioneersIndex->getPageN();
    }

    // Clean up and finish
    fclose($file);
}

/**
 * Write multiple rows of an already-open CSV.
 * @param resource $file The handle of the file to write to.
 * @param array $data The data to write.
 */
function writeData($file, $data) {
    foreach ($data as $datum) {
        fputcsv($file, $datum);
    }
}
