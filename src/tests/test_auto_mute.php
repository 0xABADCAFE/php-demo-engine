<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';


$oDecay = new Audio\Signal\Envelope\DecayPulse(1.0, 0.05);

$oMuter = new Audio\Signal\AutoMuteSilence($oDecay, 0.1, 0.01);

for ($i = 0; $i < 1000; ++$i) {
    $oMuter->emit();
}
