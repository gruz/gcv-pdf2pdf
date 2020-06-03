<?php

namespace GvcPdf2Pdf;

use Illuminate\Support\Arr;
use setasign\Fpdi\PdfReader;

class PdfTextApply
{
    use Traits\Vector;

    private $jsonFile;

    private $pdfSourceFile;

    private $pdfOutputFile;

    private $wordBox = [];

    public function __construct($jsonFile, $pdfSourceFile, $pdfOutputFile)
    {
        $this->jsonFile = $jsonFile;
        $this->pdfSourceFile = $pdfSourceFile;
        $this->pdfOutputFile = $pdfOutputFile;

        // May be used for debug purposes
        $this->wordBox = new \stdClass();
        $this->wordBox->show = false;
        $this->wordBox->color = [255, 20, 0];
        $this->wordBox->opacity = 1;

        $this->wordText = new \stdClass();
        $this->wordText->show = true;
        $this->wordText->color = [0, 0, 255];
        $this->wordText->opacity = 0.0;
        $this->wordText->fontFamily = 'DejaVu';

        // $pdf->SetFont('DejaVu','',14);

        return $this;
    }

    public function showText($opacity = 0.5, $color = [0, 0, 255] )
    {
        $this->wordText->color = $color;
        $this->wordText->opacity = $opacity;
        return $this;
    }

    public function hideText($opacity = 0.0)
    {
        $this->wordText->opacity = $opacity;
        return $this;
    }

    public function run()
    {
        $json = json_decode(file_get_contents($this->jsonFile), true);

        $responses = Arr::get($json, 'responses');

        $pdf = new FPDFHelper('P', 'pt');
        // $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

        $pdf->setSourceFile($this->pdfSourceFile);
        list($r, $g, $b)  = $this->wordBox->color;
        $pdf->SetFillColor($r, $g, $b);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY(0, 0);

        foreach ($responses as $pageNumber => $response) {
            $page = Arr::get($response, 'fullTextAnnotation.pages.0');
            if (empty($page)) {
                continue;
            }

            $width = $page['width'];
            $height = $page['height'];

            // echo PHP_EOL . '$width = ' . $width . ' | $height = ' . $height . PHP_EOL;
            // $words = $this->getWordsFromBlocks($page['blocks']);
            $paragraphs = $this->getParagraphsFromBlocks($page['blocks']);

            // We import only page 1
            $pdf->AddPage();
            $tpl = $pdf->importPage($pageNumber + 1, PdfReader\PageBoundaries::MEDIA_BOX);
            // $size = $pdf->getImportedPageSize($tpl);
            $pdf->useTemplate($tpl, 0, 0, null, null, true);

            foreach ($paragraphs as $paragraph) {
                // if (!empty($paragraph['coords'][0]['x'])) continue;
                // print_r($paragraph['coords']);

                $text = $paragraph['text'];
                $this->text = $text;
// if ('kiwico.com/store' !== trim($text)) { continue;}
                $coords = $this->getFigureParams($paragraph['coords'], $width, $height);
                // print_r($coords);

                if (false == $coords) {
                    continue;
                }

                $xs = $coords['startX'];
                $ys = $coords['startY'];
                $w = $coords['w'];
                $h = $coords['h'];
// if ($h == 0) {
//     print_r($paragraph['coords']);
//     print_r($coords);
//     exit;
// }
                $angle = $coords['rotate'];

                if ($this->wordBox->show) {
                    $pdf->SetAlpha($this->wordBox->opacity);
                    $pdf->Rect($xs, $ys, $w, $h, 'DF');
                }



                // echo '$text = ' . $text . ' | $xs = ' . $xs . ' | $ys = ' . $ys;

                // Set font and color
                list($r, $g, $b)  = $this->wordText->color;
                $pdf->SetTextColor($r, $g, $b); // RGB

                $pdf->SetFont($this->wordText->fontFamily, null, 12);
                $pdf->SetAlpha($this->wordText->opacity);

                $pdf->SetFont('Arial');
                $nb = $pdf->NbLines($w, $text);
                $pdf->SetFont($this->wordText->fontFamily);

                $pdf->SetFont($this->wordText->fontFamily, null, floor($h/$nb));

                $fontSize = $this->adjustFontSize($pdf, $w, $h/$nb, $text);

                // echo $nb  . '|' . $h/$nb . '|font-size = ' . $fontSize . ' | h = ' . $h . ' | w = ' . $w . '|' . $text . PHP_EOL;

                $pdf->SetXY($xs, $ys);
                $pdf->Rotate($angle,$xs,$ys);
                $pdf->MultiCell($w, $h/$nb, $text, 1, 'L', true);
                $pdf->Rotate(0);
            }
        }

        $pdf->Output($this->pdfOutputFile, 'F');
    }

    /**
     * Tries to determine the maximum font size possible to place in the bounding box provided by GVC.
     *
     * @param FPDFHelper $pdf
     * @param float $w Block width
     * @param float $h Block height
     * @param string $text Text
     * @return float Font size in pt
     */
    public function adjustFontSize(FPDFHelper $pdf, float $w, float $h, string $text)
    {
        $doDebug = false;
        $coef = 0.9;
        $step = 0.1;

        $fontSize = $h;
        $pdf->SetFontSize($fontSize);
        $lines = explode(PHP_EOL, $text);
        $stringWidths = [];
        foreach ($lines as $line) {
            $stringWidths[$line] = $pdf->GetStringWidth($line);
        }
        $textWidth = max($stringWidths);
        $longestText = array_keys($stringWidths,$textWidth);
        $longestText = $longestText[0];

        if ($doDebug) {
            $debug = [
                'text' => $text,
                'w' => $w,
                'h' => $h,
                'stringWidths' => $stringWidths,
                'textWidth' => $textWidth,
                'maxWith' => $w*$coef,
                'iterate?' => $textWidth > $w*$coef,
            ];
            print_r($debug);
        }

        // if ($textWidth > $w*$coef) {
        //     $fontSize *= 1.5; 
        // }

        while($textWidth > $w*$coef) {
            $debug = [];
            // $stringWidths = [];
            $fontSize = $fontSize - $step;
            $pdf->SetFontSize($fontSize);

            // foreach ($lines as $line) {
            //     $stringWidths[$line] = $pdf->GetStringWidth($line);
            // }

            // $textWidth = max($stringWidths);
            $textWidth = $pdf->GetStringWidth($longestText);


            if ($doDebug) {
                // $debug['stringWidths'] = $stringWidths;
                $debug['textWidth'] = $textWidth;
                $debug['maxWith'] = $w*$coef;

                print_r($debug);
            }

            if ($fontSize < $step) {
                break;
            }
        }

        if ($fontSize < 0) {
            $fontSize = $step;
        }

        if ($doDebug) {
            echo '$fontSize = ' . $fontSize . PHP_EOL;
        }

        return $fontSize;
    }

    public function getParagraphsFromBlocks(array $blocks)
    {
        $results = [];
        $paragraphs = Arr::get($blocks, '0.paragraphs');

        foreach ($blocks as $paragraphs) {
            foreach ($paragraphs['paragraphs'] as $paragraph) {
                $coords = Arr::get($paragraph, 'boundingBox.normalizedVertices');
                $paragraphText = [];

                foreach ($paragraph['words'] as $word) {


                    $symbols = $word['symbols'];
                    $text = [];
                    foreach ($symbols as $symbol) {
                        $text[] = $symbol['text'];
                        $break = Arr::get($symbol, 'property.detectedBreak.type', null);
                        if ($break) {
                            switch ($break) {
                                case 'LINE_BREAK':
                                    $text[] = PHP_EOL;
                                    break;
                                case 'EOL_SURE_SPACE':
                                    $text[] = PHP_EOL;
                                    break;
                                case 'SPACE':
                                    $text[] = ' ';
                                    break;

                                default:
                                    // $text[] = '';
                                    $text[] = ' ';
                                    break;
                            }
                        }
                    }

                    $paragraphText[] = implode('', $text);
                }

                $results[] = [
                    'text' => implode('', $paragraphText),
                    'coords' => $coords,
                ];
            }
        }


        return $results;
    }

    /**
     * Currently not used.
     *
     * Overlaying single words works better from the point view of placement.
     * But single words don't work for phrase matching.
     * Leave this function here just in case.
     *
     * @param array $blocks
     * @return void
     */
    public function getWordsFromBlocks(array $blocks)
    {
        $results = [];
        $paragraphs = Arr::get($blocks, '0.paragraphs');

        foreach ($blocks as $paragraphs) {
            foreach ($paragraphs['paragraphs'] as $paragraph) {
                foreach ($paragraph['words'] as $word) {
                    $coords = Arr::get($word, 'boundingBox.normalizedVertices');
                    $result = [];
                    $symbols = $word['symbols'];
                    $text = Arr::pluck($symbols, 'text');
                    $result = [
                        'text' => implode('', $text),
                        'coords' => $coords,
                    ];
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * Determines the angle of block rotation, width, height and start point.
     *
     * GVC returns coordinates of a square bounding block. We need to know if the text is
     * horizontal or vertical.
     * We can only determine wheter 0, 90, -90 and 180 rotation.
     * For a 35% anglel on an image we cannot determine it.
     * So the text will be applied as horizontal in this case.
     *
     * @param array $coord
     * @param float $width
     * @param float $height
     * @return array
     */
    public function getFigureParams(array $coordsPercent, float $width, float $height) {
        $coord = array_map(function($item) use ($width, $height){
            if (!\array_key_exists('x', $item)) {
                $item['x'] = 0;
            }
            if (!\array_key_exists('y', $item)) {
                $item['y'] = 0;
            }
            return $item;
        },$coordsPercent);

        if ($coord[0]['x'] < 0) {
            $correct = -$coord[0]['x'];
            $coord[0]['x'] = 0;
            $coord[2]['x'] = $coord[2]['x']+$correct;
            $coord[3]['x'] = 0;
        }

        $coord = array_map(function($item) use ($width, $height){
            $item['x'] *= $width;
            $item['y'] *= $height;
            return $item;
        },$coord);

        // $coord = array_map(function($item) use ($width, $height){
        //     $item['x'] = round($item['x']);
        //     $item['y'] = round($item['y']);
        //     return $item;
        // },$coord);

        if ($coord[0] === $coord[1]) {
            return false;
        }

            // print_r($coordsPercent);
            // print_r($coord);

        $rotate = $this->getAngleToAxisX($coord[0], $coord[1]);
        $check = $coord[1]['y'] - $coord[0]['y'];
        $rotate = round($rotate);
        if ($check > 0) {
            $rotate = -$rotate;
        }


        $w = $this->calculateDistance($coord[0]['x'], $coord[0]['y'], $coord[1]['x'], $coord[1]['y']);
        $h = $this->calculateDistance($coord[1]['x'], $coord[1]['y'], $coord[2]['x'], $coord[2]['y']);

        $return = [
            'h' => $h,
            'w' => $w,
            'rotate' => $rotate,
            'startX' => $coord[0]['x'],
            'startY' => $coord[0]['y'],
        ];

        // if (trim($this->text) === '208194591') {
        // // if ($rotate > 40 || $rotate < -40) {
        //     echo $this->text . PHP_EOL;
        //     print_r($coordsPercent);
        //     print_r($coord);
        //     print_r($return);
        //     $handle = fopen ("php://stdin","r");
        //     fgets($handle);
        //     fclose($handle);
        // }

        return $return;
    }
}
