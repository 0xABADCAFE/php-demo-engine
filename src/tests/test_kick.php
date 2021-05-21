<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOscillator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Sine,
    22.5
);

$oPitchEnv = new Audio\Signal\Envelope\DecayPulse(
    32.0,
    0.07
);
$oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
    1.0,
    0.1
);
$oOscillator
    ->setPitchModulator($oPitchEnv)
    ->setEnvelope($oVolumeEnv);

echo "PHP Demo Engine: Basic Oscillator test\n";

// Open the audio
$oPCMOut = new Audio\Output\APlay();
$oPCMOut->open();

for ($i=0; $i<10; ++$i) {
    $oOscillator->reset();
    // Chuck out the audio
    $iPackets = 150;
    while ($iPackets--) {
        $oPCMOut->write($oOscillator->emit());
    }

}
$oPCMOut->close();
