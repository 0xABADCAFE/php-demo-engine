<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: Subtractive Synthesis test\n";


$oPCMOut     = new Audio\Output\APlay;
$oPitchDecay = new Audio\Signal\Envelope\DecayPulse(24.0, 1.0);
$oModulator  = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Triangle,
    55.0
);
$oCarrier = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\Triangle,
    110.0
);

$oModulator
    ->setEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0,
            [
                [8, 5.0],
            ]
        )
    )
    ->setPitchModulator($oPitchDecay);

$oCarrier
    ->setPhaseModulator($oModulator)
    ->setEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0,
            [
                [1.0, 1.0],
                [0.0, 4.0]
            ]
        )
    )
    ->setPitchModulator($oPitchDecay);

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('osc1', $oCarrier, 0.5)
    ->addStream('osc2', $oModulator, 0.5);

$oFilter = new Audio\Signal\Filter\LowPass(
    $oMixer,
    0.7,
    0.3,
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

$iPackets = 3000;
while($iPackets--) {
    $oPCMOut->write($oFilter->emit());
}

$oPCMOut->close();
