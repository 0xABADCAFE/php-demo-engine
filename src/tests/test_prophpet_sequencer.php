<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Signal\IWaveform;
use ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio\Machine\ProPHPet;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);
$oSequencer->setTempo(141);

$oWhammyEnv = new Envelope\Shape(
    -0.25,
    [
        [0.0, 1.5],
        [-12, 0.5],
    ]
);

$oElectricGuitar = new ProPHPet(6);
$oElectricGuitar
    // Oscillator 1 config
    ->assignWaveform(IWaveform::SINE_SAW, ProPHPet::TARGET_OSC_1)
    ->setFrequencyRatio(1.00, ProPHPet::TARGET_OSC_1)
    ->setLevel(0.25, ProPHPet::TARGET_OSC_1)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 2.5), ProPHPet::TARGET_OSC_1)
    ->assignPitchEnvelope($oWhammyEnv, ProPHPet::TARGET_OSC_1)

    // Oscillator 2 config
    ->assignWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_2)
    ->setFrequencyRatio(1.995, ProPHPet::TARGET_OSC_2)
    ->setLevel(1.0,  ProPHPet::TARGET_OSC_2)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.7), ProPHPet::TARGET_OSC_2)
    ->assignPitchEnvelope($oWhammyEnv, ProPHPet::TARGET_OSC_2)

    // Modulation
    ->setPhaseModulationIndex(0.3)

    // Filter - Auto wah
    ->setFilterMode(ProPHPet::FILTER_BANDPASS)
    ->setFilterCutoff(0.125)
    ->setFilterResonance(0.3)

    // LFO Config
    ->setLevel(0.05, ProPHPet::TARGET_PITCH_LFO)
    ->setLFORate(4.5, ProPHPet::TARGET_PITCH_LFO)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_1)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_2)
    ->setLevel(0.8, ProPHPet::TARGET_CUTOFF_LFO)
    ->setLFORate(0.8, ProPHPet::TARGET_CUTOFF_LFO)
    ->enableCutoffLFO()
    // Output
    ->setOutputLevel(1.0)
;


$oSequencer
    ->addMachine('sub', $oElectricGuitar)
;
// Strum some chords
$oSequencer->allocatePattern('sub', [0, 3])
    ->addEvent(Event::noteOn('E2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 90), 1, 1, 0)
    ->addEvent(Event::noteOn('E3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('G#3', 45), 3, 3, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 5, 0)
;

$oSequencer->allocatePattern('sub', [1])
    ->addEvent(Event::noteOn('G2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 40), 1, 1, 0)
    ->addEvent(Event::noteOn('D3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('G3', 75), 3, 3, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('G4', 70), 5, 5, 0)
;
$oSequencer->allocatePattern('sub', [2])
    ->addEvent(Event::noteOn('A2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('A2', 90), 1, 1, 0)
    ->addEvent(Event::noteOn('G3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('B3', 75), 3, 3, 0)

    ->addEvent(Event::setNote('C#3'), 3, 16)

    ->addEvent(Event::noteOn('E4', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 5, 0)
    ->addEvent(Event::noteOn('G2', 30), 0, 28)
;

if (!empty($_SERVER['argv'][1])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][1] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
