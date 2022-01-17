<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);
$oSequencer->setTempo(141);

$oFM = new Audio\Machine\PHProphpet(6);
$oFM
    ->assignWaveform(Audio\Signal\IWaveform::SINE_SAW, Audio\Machine\PHProphpet::TARGET_OSC_1)
    ->setOscillator1Ratio(1.00)
    ->setOscillator1LevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 2.5)
    )
    ->setPhaseModulationIndex(0.25)
    ->setOscillator1Mix(0.25)

    ->assignWaveform(Audio\Signal\IWaveform::SQUARE, Audio\Machine\PHProphpet::TARGET_OSC_2)
    ->setOscillator2Ratio(1.995)
    ->setOscillator2LevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.7)
    )
    ->setLFODepth(0.05, Audio\Machine\PHProphpet::TARGET_PITCH_LFO)
    ->setLFORate(4.5, Audio\Machine\PHProphpet::TARGET_PITCH_LFO)
    ->enablePitchLFO(true, true)
    ->setOutputLevel(0.4)
;


$oSequencer
    ->addMachine('sub', $oFM)
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
