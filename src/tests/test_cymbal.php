<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: Cymbal Synthesis test\n";

$oSquare = new Audio\Signal\Waveform\Square();

$oOsc1 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1047
);

$oOsc2 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1481
);
$oOsc2->setPhaseModulator($oOsc1);

$oOsc3 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1109
);

$oOsc4 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    2490
);
$oOsc4->setPhaseModulator($oOsc3);

$oOsc5 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1397
);

$oOsc6 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    2490
);
$oOsc6->setPhaseModulator($oOsc5);

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('p1', $oOsc2, 0.2)
    ->addStream('p2', $oOsc4, 0.2)
    ->addStream('p3', $oOsc6, 0.2);


$oPCMOut  = new Audio\Output\APlay;
$oPCMOut->open();

$iPackets = 100;
while($iPackets--) {
    $oPCMOut->write($oMixer->emit());
}

$oPCMOut->close();
