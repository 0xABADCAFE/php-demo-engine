<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: Cymbal Synthesis test\n";

$oOsc1 = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\AliasedPulse(0.7),
    1047
);
$oOsc2 = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\AliasedSquare(),
    2490
);
$oOsc2
    ->setPhaseModulator($oOsc1)
    ->setPitchModulator($oOsc1);

$oFilter1 = new Audio\Signal\Filter\BandPass(
    $oOsc2,
    1.0,
    0.1,
    new Audio\Signal\Envelope\DecayPulse(1.0, 0.02, 0.25)
);

$oFilter2 = new Audio\Signal\Filter\LowPass(
    $oOsc2,
    1.0,
    0.0,
    new Audio\Signal\Envelope\Shape(
        1.0,
        [
            [0.0, 0.2],
            [1.0, 1]
        ]
    )
);



$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addInputStream('f1', $oFilter1, 0.02)
    ->addInputStream('f2', $oFilter2, 0.01)

;

$oPCMOut  = Audio\Output\Piped::create();
$oPCMOut->open();

$iPackets = 200;
while($iPackets--) {
    $oPCMOut->write($oMixer->emit());
}

$oPCMOut->close();
