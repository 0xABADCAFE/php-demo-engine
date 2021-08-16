<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oSource   = new Graphics\Image(4, 4, 1);
$oTarget   = new Graphics\Image(8, 8, 0);

$oBlitter = new Graphics\Blitter();
$oBlitter
    ->setSource($oSource)
    ->setTarget($oTarget);

$oBlitter->copy(0, 0, 2, 2, 4, 4);

\print_r($oTarget);
