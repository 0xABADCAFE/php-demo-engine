<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oDisplay = Display\Factory::get()->create('DoubleVerticalRGB', 160, 100);
$oDisplay->reset();
$oBlitter = new Graphics\Blitter();
$oBlitter->setTarget($oDisplay);

while(true) {
    $iMax = 10;
    while ($iMax--) {
        $oBlitter->fill(
            mt_rand(0, 0xFFFFFF),
            mt_rand(-80, 80),
            mt_rand(-50, 50),
            mt_rand(0, 160),
            mt_rand(0, 100)
        );
    }
    $oDisplay->redraw()->waitForFrame();
    usleep(10000);
}
