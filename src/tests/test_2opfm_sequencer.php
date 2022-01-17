<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);
$oSequencer->setTempo(141);

$oFM = new Audio\Machine\TwoOpFM(6);
$oFM
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE_SAW)
    ->setModulatorRatio(1.00)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 2.5)
    )
    ->setModulationIndex(0.25)
    ->setModulatorMix(0.25)
        ->setCarrierWaveform(Audio\Signal\IWaveform::SQUARE)
    ->setCarrierRatio(1.995)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.7)
    )
    ->setPitchLFODepth(0.05)
    ->setPitchLFORate(4.5)
    ->enablePitchLFO(true, true)
    ->setOutputLevel(0.2)
;


$oSequencer
    ->addMachine('fm', $oFM)
;
// Strum some chords
$oSequencer->allocatePattern('fm', [0, 3])
    ->addEvent(Event::noteOn('E2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 90), 1, 1, 0)
    ->addEvent(Event::noteOn('E3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('G#3', 45), 3, 3, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 5, 0)
;

$oSequencer->allocatePattern('fm', [1])
    ->addEvent(Event::noteOn('G2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 40), 1, 1, 0)
    ->addEvent(Event::noteOn('D3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('G3', 75), 3, 3, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('G4', 70), 5, 5, 0)
;
$oSequencer->allocatePattern('fm', [2])
    ->addEvent(Event::noteOn('A2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('A2', 90), 1, 1, 0)
    ->addEvent(Event::noteOn('G3', 80), 2, 2, 0)
    ->addEvent(Event::noteOn('B3', 75), 3, 3, 0)
    ->addEvent(Event::noteOn('E4', 70), 4, 4, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 5, 0)
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
