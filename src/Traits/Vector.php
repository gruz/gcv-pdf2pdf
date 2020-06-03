<?php
/**
 * @see http://www.fpdf.org/en/script/script83.php
 */

namespace GvcPdf2Pdf\Traits;

trait Vector
{
    public function buildVector($p1, $p2)
    {
        return [
            'x' => $p2['x'] - $p1['x'],
            'y' => $p2['y'] - $p1['y'],
        ];
    }

    public function norm($vec)
    {
        $norm = 0;
        $components = count($vec);

        for ($i = 0; $i < $components; $i++)
            $norm += $vec[$i] * $vec[$i];

        return sqrt($norm);
    }

    public function dot($vec1, $vec2)
    {
        $prod = 0;
        $components = count($vec1);

        for ($i = 0; $i < $components; $i++)
            $prod += ($vec1[$i] * $vec2[$i]);

        return $prod;
    }

    public function getAngleFromVectors($v1, $v2) {
        $v1 = \array_values($v1);
        $v2 = \array_values($v2);
        $ang = acos($this->dot($v1, $v2) / ($this->norm($v1) * $this->norm($v2)));
        return rad2deg($ang);
    }

    public function getAngleFromPoints($coords1, $coords2) {
        $v1 = $this->buildVector($coords1[0], $coords1[1]);
        $v2 = $this->buildVector($coords2[0], $coords2[1]);

        return $this->getAngleFromVectors($v1, $v2);
    }

    public function getAngleToAxisX($p1, $p2) {

        $v1 = $this->buildVector($p1, $p2);
        $v2 = $this->buildVector(['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0]);

        return $this->getAngleFromVectors($v1, $v2);
    }

    public function calculateDistance($x1,$y1,$x2,$y2)
    {
        return sqrt( ($x2 - $x1) ** 2 + ($y2 - $y1) ** 2 );
    }
}
