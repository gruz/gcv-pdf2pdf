# Overlay text from Google Vision Cloud (OCR) over the original PDF-as-image to create a searchable pdf

## When needed

You have a PDF as image. You have recognized the text by Google vision cloud  (gvc) OCR service.
And now want to apply the text to the pdf to make it searchable.

> Note! This too makes sense only for PDFs recognized with GVC.
> For images recognized by GVC you can use better toosls like [https://github.com/dinosauria123/gcv2hocr/].
> You may also try to use [https://github.com/mah-jp/pdf4search]. I failed to learn Japaneese.

## Problems

It's not possible to use the same font and the same size of text so the text is added approximately to the same place,
where it's on image. Do not expect 100% good resut.

## Quick Examples

In `exmaples` folder you can find in pair of files (PDF-as-image and generater by Google vision cloud JSON) and
a couple of out files (for production with hidden text and for debug with visible text).

To get the idea see a video demonstrating results [https://streamable.com/r2gyk9]

## Installation

> Surely you need PHP.

```bash
git clone ...
cd folder
composer install

```

## Running example

Go to the project folder if not there and run

```bash
php example/example.php
```

As a result you get regenerated out pdfs.

## Usage

```php

use GvcPdf2Pdf\PdfTextApply;

$jsonFile = __DIR__ .'/01-in-google-vision-cloud.json';
$pdfSourceFile = __DIR__ .'/01-in-pdf-as-image.pdf';

$pdfOutFile1 = __DIR__ .'/01-out-pdf-with-text-hidden.pdf';
$pdf = new PdfTextApply($jsonFile, $pdfSourceFile, $pdfOutFile1);
$pdf->run();
```
