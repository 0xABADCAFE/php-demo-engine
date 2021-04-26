<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iWidth  = 128;
$iHeight = 50;

$fScale  = 255.0/$iWidth;

$oDisplay = Display\Factory::get()->create('RGBASCIIOverRGB', $iWidth, $iHeight);
$oDisplay->reset();

$oPixels = $oDisplay->getPixels();
$sChars  = &$oDisplay->getCharacterBuffer();
$iOffset = 0;
$iOffset2= 0;
for ($iY = 0; $iY < $iHeight; ++$iY) {
    for ($iX = 0; $iX < $iWidth; ++$iX) {
        $iGreyBG = (int)($iX * $fScale);
        $iGreyFG = 255 - $iGreyBG;
        $oPixels[$iOffset++] = ($iGreyFG << 40) | $iGreyBG;
        $sChars[$iOffset2++] = 'X';
    }
    $iOffset2++;
}


$oDisplay
    ->redraw()
    ->waitForFrame();
