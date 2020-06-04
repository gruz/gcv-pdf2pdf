<?php

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

use GvcPdf2Pdf\PdfTextApply;

$jsonFile = __DIR__ .'/01-in-google-vision-cloud.json';
$pdfSourceFile = __DIR__ .'/01-in-pdf-as-image.pdf';

$pdfOutFile1 = __DIR__ .'/01-out-pdf-with-text-hidden.pdf';
$pdf = new PdfTextApply($jsonFile, $pdfSourceFile, $pdfOutFile1);
$pdf->watermark->text = 'g r u z . m l';
$pdf->run();

$pdfOutFile2 = __DIR__ .'/01-out-pdf-with-text-visible-for-debug.pdf';
$pdf = new PdfTextApply($jsonFile, $pdfSourceFile, $pdfOutFile2);
$pdf->showText()->run();
