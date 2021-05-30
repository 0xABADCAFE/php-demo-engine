<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOscillator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\WhiteNoise,
    22.5
);

// Open the audio
$oPCMOut1 = new Audio\Output\Wav('noise.wav');
$oPCMOut2 = Audio\Output\Piped::create();
$oPCMOut1->open();
$oPCMOut2->open();
// Chuck out the audio
$iPackets = 2000;
while ($iPackets--) {
    $oPacket = $oOscillator->emit()->scaleBy(0.5);
    $oPCMOut1->write($oPacket);
    $oPCMOut2->write($oPacket);
}

$oPCMOut1->close();
$oPCMOut2->close();
