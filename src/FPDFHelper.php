<?php

namespace GvcPdf2Pdf;

use setasign\Fpdi\Tfpdf\Fpdi;
use GvcPdf2Pdf\FPDFTraits\Alpha;
use GvcPdf2Pdf\FPDFTraits\Rotate;

class FPDFHelper extends Fpdi
{
    use Alpha;
    use Rotate;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    }

    /**
     * Computes the number of lines a MultiCell of width w will take
     *
     * @param float $w pt
     * @param string $txt
     * @return int
     *
     * @see http://www.fpdf.org/en/script/script3.php
     */
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }

        return $nl;
    }
}
