<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOscillator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Pulse,
    22.5
);

$oPitchEnv = new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [84.0, 10]
    ]
);
$oOscillator->setPitchModulator($oPitchEnv);

echo "PHP Demo Engine: Basic Oscillator test\n";

// Open the audio
$oPCMOut = new Audio\Output\Wav('alias.wav');
$oPCMOut->open();

// Chuck out the audio
$iPackets = 2000;
while ($iPackets--) {
    $oPCMOut->write($oOscillator->emit());
}

$oPCMOut->close();
