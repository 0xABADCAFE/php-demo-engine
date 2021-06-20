<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOsc = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\QuadrantMutator(
        new Audio\Signal\Waveform\Sine()
    ),
    220
);

echo "PHP Demo Engine: Basic Oscillator test\n";

// Open the audio
//$oPCMOut = Audio\Output\Piped::create();
$oPCMOut = new Audio\Output\Wav('mutate.wav');
$oPCMOut->open();

for ($i=0; $i<100; ++$i) {
    $oPCMOut->write($oOsc->emit());
}


$oPCMOut->close();
