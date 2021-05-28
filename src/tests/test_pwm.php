<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOscillator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Pulse(
        0.7,
        new Audio\Signal\Oscillator\LFO(
            new Audio\Signal\Waveform\Sine(),
            0.1
        )
    ),
    32
);

//$oPitchEnv = new Audio\Signal\Envelope\DecayPulse(24.0, 1);

//$oOscillator->setPitchModulator($oPitchEnv);

$oFilter = new Audio\Signal\Filter\LowPass(
    $oOscillator,
    0.7,
    0.2,
    new Audio\Signal\Oscillator\LFOZeroToOne(
        new Audio\Signal\Waveform\Sine(),
        0.25,
        0.5
    )
);

// Open the audio
$oPCMOut = new Audio\Output\APlay();
$oPCMOut->open();

// Chuck out the audio
$iPackets = 5000;
while ($iPackets--) {
    $oPCMOut->write($oFilter->emit()->scaleBy(0.5));
}

$oPCMOut->close();
