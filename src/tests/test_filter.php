<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oOscillator = new Audio\Signal\Oscillator\Sound(
    new Audio\Signal\Waveform\WhiteNoise()
);

$oSweep   = new Audio\Signal\Envelope\DecayPulse(
    1.0,
    0.5,
    0.01
);

$aFilters = [
    'lowpass'  => new Audio\Signal\Filter\LowPass($oOscillator, 1.0, 0.0),
    'bandpass' => new Audio\Signal\Filter\HighPass($oOscillator, 1.0, 0.0),
    'highpass' => new Audio\Signal\Filter\HighPass($oOscillator, 1.0, 0.0),
    'notch'    => new Audio\Signal\Filter\NotchReject($oOscillator, 1.0, 0.0)
];

$oSilence = Audio\Signal\Packet::create();

// Open the audio
$oPCMOut = new Audio\Output\APlay();
$oPCMOut->open();

foreach ($aFilters as $sName => $oFilter) {

    echo "Testing ", $sName, "...\n";

    $oSweep->reset();
    $oFilter->setCutoffControl($oSweep);

    // Chuck out the audio
    $iPackets = 1000;
    while ($iPackets--) {
        $oPacket = $oFilter->emit()->scaleBy(0.5);
        $oPCMOut->write($oPacket);
    }
    for ($i = 0; $i < 50; ++$i) {
        $oPCMOut->write($oSilence);
    }

}
$oPCMOut->close();
