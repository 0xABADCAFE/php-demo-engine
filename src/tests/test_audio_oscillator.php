<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

// Very simple fast attack, decay envelope
$oEnvelope = new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [0.5, 0.01],
        [0.0, 0.75]
    ]
);

// Little bit of vibrato
$oLFO = new Audio\Signal\Oscillator\LFO(
    new Audio\Signal\Waveform\Sine(),
    4.0,
    0.15
);

// Basic audio oscillator
$oOsc1 = new Audio\Signal\Oscillator\Sound(
    null
);

// Attach the envelope and vibrato
$oOsc1
    ->setLevelEnvelope($oEnvelope)
    ->setPitchModulator($oLFO);

$oOsc2 = clone $oOsc1;
$oOsc3 = clone $oOsc1;

// Set of waveforms to test
$aWaveforms = [
    'Sine'     => new Audio\Signal\Waveform\Sine(),
    'Triangle' => new Audio\Signal\Waveform\Triangle(),
    'Sawtooth' => new Audio\Signal\Waveform\Saw(),
    'Square'   => new Audio\Signal\Waveform\Square(),
    'Pulse'    => new Audio\Signal\Waveform\Pulse(0.125)
//    'Noise'    => new Audio\Signal\Waveform\WhiteNoise()
];

// Semitone notes relative to base pitch
$aNotes1 = [
    'C3', 'D3', 'E3', 'F3', 'G3', 'A3', 'B3', 'C4'
];

$aNotes2 = [
    'E3', 'F3', 'G3', 'A3', 'B3', 'C4', 'D4', 'E4'
];

$aNotes3 = [
    'G3', 'A3', 'B3', 'C4', 'D4', 'E4', 'F4', 'G4'
];

$oMixer = new Audio\Signal\FixedMixer();
$oMixer
    ->addStream('ch0', $oOsc1, 0.5)
    ->addStream('ch1', $oOsc2, 0.5)
    ->addStream('ch2', $oOsc3, 0.5);

echo "PHP Demo Engine: Basic Oscillator test\n";

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
$oPCMOut->open();

foreach ($aWaveforms as $sName => $oWaveform) {
    echo "Testing: ", $sName, ": ";
    $oOsc1->setWaveform($oWaveform);
    $oOsc2->setWaveform($oWaveform);
    $oOsc3->setWaveform($oWaveform);
    foreach ($aNotes1 as $i => $sNote1) {
        $sNote2 = $aNotes2[$i];
        $sNote3 = $aNotes3[$i];
        $fFrequency1 = Audio\Note::getFrequency($sNote1);
        $fFrequency2 = Audio\Note::getFrequency($sNote2);
        $fFrequency3 = Audio\Note::getFrequency($sNote3);

        printf("%s [%.2fHz] ", $sNote1, $fFrequency1);
        $oMixer->reset();
        $oOsc1->setFrequency($fFrequency1);
        $oOsc2->setFrequency($fFrequency2);
        $oOsc3->setFrequency($fFrequency3);

        // Chuck out the audio
        $iPackets = 200;
        while ($iPackets--) {
            $oPCMOut->write($oMixer->emit());
        }
    }
    echo "\n";
}
$oPCMOut->close();
