<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oSource   = \SPLFixedArray::fromArray(array_fill(0, 4*4, 'x'));
$oTarget   = \SPLFixedArray::fromArray(array_fill(0, 8*8, '-'));

$oBlitter = new Graphics\Blitter();
$oBlitter
    ->setSource($oSource, 4, 4)
    ->setTarget($oTarget, 8, 8);

$oBlitter->copy(0, 0, 2, 2, 4, 4);

print_r($oTarget);
