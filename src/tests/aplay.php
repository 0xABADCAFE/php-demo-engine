<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oPCMOut = new Audio\PCMOutput;

$oModulator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Saw,
    110.0
);
$oModulator->setEnvelope(
    new Audio\Signal\Envelope\Shape(
        0.0,
        [
            [8, 5.0],
        ]
    )
);


$oCarrier = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Sine,
    220.0
);

$oCarrier->setPhaseModulator($oModulator);
$oCarrier->setEnvelope(
    new Audio\Signal\Envelope\Shape(
        0.0,
        [
            [1.0, 1.0],
            [0.0, 4.0]
        ]
    )
);

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('osc1', $oCarrier, 0.5)
    ->addStream('osc2', $oModulator, 0.5);

$oFilter = new Audio\Signal\Filter\LowPass(
    $oMixer,
    0.7,
    0.5,
    new Audio\Signal\Envelope\DecayPulse(
        1.0,
        1.0
    ),
    new Audio\Signal\Envelope\Shape(
        0.0,
        [
            [2.0, 2.0]
        ]
    )
);


$oPCMOut->open();

$iPackets = 5000;
while($iPackets--) {
    $oPCMOut->write($oFilter->emit());
}

$oPCMOut->close();
