<?php

/**
 * A mofified vestion of the @see liml
 *
 * Adds multiCell support
 *
 * @see http://www.fpdf.org/en/script/script62.php
 */

namespace GvcPdf2Pdf\FPDFTraits;

trait CellFit
{
    //Cell with horizontal scaling if text is too wide
    function CellFit($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $scale=false, $force=true, $multiCell = false)
    {
        // if ("会" === $txt) {
        //     $a = 1;
        // }
        //Get string width
        if ($multiCell) {
            $stringWidths = [];
            $lines = explode(PHP_EOL, $txt);
            foreach ($lines as $line) {
                $stringWidths[$line] = $this->GetStringWidth($line);
            }
            $str_width = max($stringWidths);
            $longestText = array_keys($stringWidths,$str_width);
            $longestText = $longestText[0];
        } else {
            $str_width=$this->GetStringWidth($txt);
            $longestText = $txt;
        }

        if (empty($str_width)) {
            $str_width = 1;
        }

        //Calculate ratio to fit cell
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $ratio = ($w-$this->cMargin*2)/$str_width;

        $fit = ($ratio < 1 || ($ratio > 1 && $force));
        if ($fit)
        {
            if ($scale)
            {
                //Calculate horizontal scaling
                $horiz_scale=$ratio*100.0;
                //Set horizontal scaling
                $this->_out(sprintf('BT %.2F Tz ET',$horiz_scale));
            }
            else
            {
                //Calculate character spacing in points
                $char_space=($w-$this->cMargin*2-$str_width)/max(strlen($longestText)-1,1)*$this->k;
                //Set character spacing
                $this->_out(sprintf('BT %.2F Tc ET',$char_space));
            }
            //Override user alignment (since text will fill up cell)
            $align='';
        }

        //Pass on to Cell method
        if ($multiCell) {
            $this->MultiCell($w,$h,$txt,$border,$align,$fill);
        } else {
            $this->Cell($w,$h,$txt,$border,$ln,$align,$fill,$link);
        }

        //Reset character spacing/horizontal scaling
        if ($fit)
            $this->_out('BT '.($scale ? '100 Tz' : '0 Tc').' ET');
    }


    //Cell with horizontal scaling only if necessary
    function CellFitScale($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $multiCell = false)
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,false, $multiCell);
    }

    //Cell with horizontal scaling always
    function CellFitScaleForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $multiCell = false)
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,true,true, $multiCell);
    }

    //Cell with character spacing only if necessary
    function CellFitSpace($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $multiCell = false)
    {
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,false, $multiCell);
    }

    //Cell with character spacing always
    function CellFitSpaceForce($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='', $multiCell = false)
    {
        //Same as calling CellFit directly
        $this->CellFit($w,$h,$txt,$border,$ln,$align,$fill,$link,false,true, $multiCell);
    }
}
