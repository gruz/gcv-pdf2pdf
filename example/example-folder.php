<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use GvcPdf2Pdf\PdfTextApply;

/** 
 * This block is used for debug purposes
 * 
 * Enable debug to run only in selected in $stopNames base filenames.
 * You may also force to regenerate pereviously created files.
 */
$regenerate = false;
$regenerate = true;

$debug = true;
$debug = false;

// If enabled debug, I can process only selected basenames if files.
$stopNames = [
    // '384-175003-2005',
    // '356-724043-2005',
    // '190-721628-2005A',
    // '384-179450-2005C',
    '347-726418-2005',
];
// Error handler to thrown an exception on any php notice
// I make it work to exit on any PHP notice while processing a folder.
// I assume if there is a php notice, then something is wrong and must be fixed.
if (!$debug) {
    function exception_error_handler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            // This error code is not included in error_reporting
            return;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    set_error_handler("exception_error_handler");
}

$folderIn = __DIR__.'/in/';
$folderOut = __DIR__.'/out/';
$files = glob($folderIn.'*.pdf');
$total = count($files);

foreach ($files as $c  => $pdfFilePath) {
    $name = basename($pdfFilePath, '.pdf');

    // if ($c > 3) exit;

    if ($debug && !in_array($name, $stopNames)) continue;

    $jsonFilePath = $folderIn . $name .'.json';

    if (is_file($folderIn . $name .'.json')) {
        echo 'Processing ' . ($c+1) . ' of ' . $total . ' "' . $name . '" pair ...';

        try {
            $pdfOutFile = $folderOut . $name . '.pdf';
            if ($debug || $regenerate || (!$regenerate && !is_file($pdfOutFile))) {
                $pdf = new PdfTextApply($jsonFilePath, $pdfFilePath, $pdfOutFile);
                $pdf->run();
            }

            $pdfOutFile = $folderOut . $name . '_debug.pdf';
            if ($debug ||  $regenerate || (!$regenerate && !is_file($pdfOutFile))) {
                $pdf = new PdfTextApply($jsonFilePath, $pdfFilePath, $pdfOutFile);
                $pdf->showText()->run();
            }

            echo ' DONE' . PHP_EOL;
        } catch (\Throwable $th) {
            echo PHP_EOL . "\t" . $name . ' : ' . PHP_EOL . $th->getMessage() . PHP_EOL;
            exit;
        }
    }
}
