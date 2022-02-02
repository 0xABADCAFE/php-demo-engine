<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

const F_INDEX = 0.5;

$oOscillator = new Audio\Signal\Oscillator\Sound(
    Audio\Signal\Waveform\Flyweight::get()->getWaveform(Audio\Signal\IWaveform::TRIANGLE),
    220.0
);

$oEnvelope = new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [1.0, 1.0],
        [1.0, 0.25],
        [0.0, 2]
    ]
);

$oPitchEnvelope =  new Audio\Signal\Envelope\Shape(
    -12.0,
    [
        [0.0, 2]
    ]
);

$oOscillator
    ->setAntialiasMode(Audio\Signal\Oscillator\Sound::ANTIALIAS_ON)
    ->setPitchEnvelope($oPitchEnvelope)
    ->setLevelEnvelope($oEnvelope)
    ->setPhaseFeedbackIndex(F_INDEX);

$oPCMOut = new Audio\Output\Wav(sprintf('output/feedback-%.2f.wav', F_INDEX));
$oPCMOut->open();
for ($j = 0; $j < 1000; ++$j) {
    $oPCMOut->write($oOscillator->emit());
}
$oPCMOut->close();

