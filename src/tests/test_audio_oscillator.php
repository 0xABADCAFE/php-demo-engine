<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

// Very simple fast attack, decay envelope
$oEnvelope = new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [0.2, 0.01],
        [0.0, 0.5]
    ]
);

// Little bit of vibrato
$oLFO = new Audio\Signal\Oscillator\LFO(
    new Audio\Signal\Waveform\Sine(),
    4.0,
    0.1
);

// Basic audio oscillator
$oOscillator = new Audio\Signal\Oscillator\Sound(
    null,
    110.0
);

// Attach the envelope and vibrato
$oOscillator
    ->setEnvelope($oEnvelope)
    ->setPitchModulator($oLFO);

// Set of waveforms to test
$aWaveforms = [
    'Sine'     => new Audio\Signal\Waveform\Sine(),
    'Triangle' => new Audio\Signal\Waveform\Triangle(),
    'Sawtooth' => new Audio\Signal\Waveform\Saw(),
    'Square'   => new Audio\Signal\Waveform\Square(),
//    'Noise'    => new Audio\Signal\Waveform\WhiteNoise()
];

// Semitone notes relative to base pitch
$aNotes = [
    0, // base frequency
    2,
    4,
    5,
    7,
    9,
    11,
    12 // 2x base frequency
];

echo "PHP Demo Engine: Basic Oscillator test\n";

// Open the audio
$oPCMOut = new Audio\PCMOutput;
$oPCMOut->open();

foreach ($aWaveforms as $sName => $oWaveform) {
    echo "Testing: ", $sName, ": ";
    $oOscillator->setWaveform($oWaveform);
    foreach ($aNotes as $iNote) {
        echo $iNote, " ";
        $oEnvelope->reset();
        // Calculate the new note frequency to use.
        $fFrequency = 110.0 * 2.0**($iNote / 12.0);
        $oOscillator->setFrequency($fFrequency);

        // Chuck out the audio
        $iPackets = 150;
        while ($iPackets--) {
            $oPCMOut->write($oOscillator->emit());
        }
    }
    echo "\n";
}
$oPCMOut->close();
