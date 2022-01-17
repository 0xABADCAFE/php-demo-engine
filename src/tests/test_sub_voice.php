<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

if (!empty($_SERVER['argv'][1])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][1] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oVoice = new Audio\Machine\Subtractive\Voice;
$oVoice
    ->setFrequency(110.0)
    ->setPhaseModulationIndex(0.5)
    ->setRingModulationIndex(0.5)
    ->setMixLevel(Audio\Machine\Subtractive\Voice::ID_OSC_1, 0)
    ->setMixLevel(Audio\Machine\Subtractive\Voice::ID_OSC_2, 0)

    //     ->setFilterMode(Audio\Machine\Subtractive\Voice::FILTER_LP)
//     ->setFilterCutoff(0.7)
//     ->setFilterResonance(0.5)
//     ->setFilterCutoffLFO(
//         new Audio\Signal\Oscillator\LFOOneToZero(
//             new Audio\Signal\Waveform\Sine(),
//             0.25
//         )
//     )
//     ->setFilterCutoffEnvelope(
//         new Audio\Signal\Envelope\DecayPulse(1.0, 0.05, 0.05)
//     )
//     ->setPhaseModulationIndex(0.6)
;

$oOutput->open();

for ($i = 0; $i < 1000; ++$i) {
    $oOutput->write($oVoice->emit());
}

$oOutput->close();
