<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOsc = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\SineSaw(),
    220
);


$oOsc->reset();
$oPCMOut = new Audio\Output\Wav('sine_saw.wav');
$oPCMOut->open();
for ($i = 0; $i < 100; ++$i) {
    $oPCMOut->write($oOsc->emit());
}
$oPCMOut->close();

