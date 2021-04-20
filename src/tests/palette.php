<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oPalette = new Graphics\Palette(256);


$oPalette->gradient([
    240 => 0x000000,
    254 => 0xFFFFFF
]);

print_r($oPalette->getEntries());
