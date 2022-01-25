<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Machine\Control\IAutomatable;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);

$oFM = new Audio\Machine\OPHPL(5);
$oFM
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE)
    ->setModulatorRatio(0.5)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [0.5, 1.0],
                [0.0, 10.0]
            ]
        )
    )
    ->setModulationIndex(0.5)
    ->setModulatorMix(0)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 0.1],
                [0.0, 12.0]
            ]
        )
    )
    ->setCarrierMix(0.5)
    ->setOutputLevel(1)
    ->setPitchLFODepth(0.03)
    ->setPitchLFORate(4.5)
    ->enablePitchLFO(true, true)
;


$oSequencer
    ->addMachine('fm', $oFM)
;

$oSequencer->allocatePattern('fm', [0])
    ->addEvent(Event::noteOn('C2', 50), 4, 0)
    ->addEvent(Event::noteOn('C4', 50), 0, 0)
    ->addEvent(Event::noteOn('E4', 50), 1, 0)
    ->addEvent(Event::noteOn('G4', 50), 2, 0)
    ->addEvent(Event::noteOn('C5', 50), 3, 0)
;


$oSequencer->allocatePattern('fm', [1])
    ->addEvent(Event::setNote('D#4'), 1, 0)
    ->addEvent(Event::noteOn('D#2', 50), 4, 0)
;

if (!empty($_SERVER['argv'][2])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][2] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
