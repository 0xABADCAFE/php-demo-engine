<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oPCMOut = new Audio\PCMOutput;

$oLFO = new Audio\Signal\Oscillator\LFO(
    new Audio\Signal\Waveform\Sine,
    3.0,
    0.1
);

$oModulator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Sine,
    220.0 * (2.0 ** (4/12))
);

$oModulator->setPitchModulator($oLFO);
$oModulator->setEnvelope(
    new Audio\Signal\Envelope\Shape(
        0.0,
        [
            [0.9, 2.0],
            [0.1, 4.0]
        ]
    )
);

$oCarrier = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Sine,
    220.0
);

$oCarrier->setPhaseModulator($oModulator);
$oCarrier->setPitchModulator($oLFO);
$oCarrier->setEnvelope(
    new Audio\Signal\Envelope\Shape(
        0.0,
        [
            [0.5, 4.0],
            [0.0, 4.0]
        ]
    )
);

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('osc1', $oCarrier, 0.75)
    ->addStream('osc2', $oModulator, 0.25);


$oPCMOut->open();

$iPackets = 5000;
while($iPackets--) {
    $oPCMOut->write($oMixer->emit());
}

$oPCMOut->close();
