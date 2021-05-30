<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: Cymbal Synthesis test\n";

$oDecay1  = new Audio\Signal\Envelope\DecayPulse(1.0, 0.5);
$oDecay2  = new Audio\Signal\Envelope\DecayPulse(1.0, 0.25);

$oSquare = new Audio\Signal\Waveform\Square();
$oOsc1 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1047
);
$oOsc1->setEnvelope($oDecay1);
$oOsc2 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1481
);
$oOsc2
    ->setPhaseModulator($oOsc1)
    ->setEnvelope($oDecay2);

$oFilter1 = new Audio\Signal\Filter\HighPass($oOsc2, 0.9);

$oOsc3 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1109
);

$oOsc3->setEnvelope($oDecay2);

$oOsc4 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    2490
);
$oOsc4
    ->setPhaseModulator($oOsc3)
    ->setEnvelope($oDecay1);

$oOsc5 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    1397
);
$oOsc5->setEnvelope($oDecay2);

$oOsc6 = new Audio\Signal\Oscillator\Sound(
    $oSquare,
    2490
);
$oOsc6
    ->setPhaseModulator($oOsc5)
    ->setEnvelope($oDecay1);

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('p1', $oFilter1, 0.2)
    //->addStream('p2', $oOsc4, 0.2)
    //->addStream('p3', $oOsc6, 0.2);
;

$oPCMOut  = Audio\Output\Piped::create();
$oPCMOut->open();

$iPackets = 1000;
while($iPackets--) {
    $oPCMOut->write($oMixer->emit());
}

$oPCMOut->close();
