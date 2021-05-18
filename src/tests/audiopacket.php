<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oPacket = Audio\Signal\Packet::create();

print_r($oPacket);

$oPacket
    ->biasBy(0.5)
    ->scaleBy(3.0)
    ->biasBy(-0.25);

print_r($oPacket);
