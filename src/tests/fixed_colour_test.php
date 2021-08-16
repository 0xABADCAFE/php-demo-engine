<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iWidth  = 120;
$iHeight = 50;


$oDisplay = Display\Factory::get()->create('PlainASCII', $iWidth, $iHeight);
$oDisplay->reset();

for ($i = 0; $i < 256; ++$i) {
    $oDisplay
        ->setBackgroundColour($i)
        ->redraw()
        ->waitForFrame();
        \usleep(500000);
}
