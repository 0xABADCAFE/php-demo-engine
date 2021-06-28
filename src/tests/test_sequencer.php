<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();

$oFMMarimba = new Audio\Machine\TwoOpFM(4);
$oFMMarimba
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE)
    ->setModulatorRatio(7.01)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.016)
    )
    ->setModulationIndex(0.2)
    ->setModulatorMix(0.15)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1.99)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.1)
    )
    ->setOutputLevel(0.35)
;
$oFMMarimba->setInsert(new Audio\Signal\Insert\DelayLoop(null, 123.0 * 3, 0.6));

$oSequencer->addMachine('marimba', $oFMMarimba);
$oSequencer->allocatePattern('marimba', [0, 4, 8, 12])
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 0, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 1, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 60), 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 3, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 48), 1, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 40), 2, 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 3, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 1, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 40), 2, 14)
;

$oSequencer->allocatePattern('marimba', [1, 5, 9, 13])
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 0, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 40), 1, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 50), 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 3, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 0, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 48), 1, 9)
    ->addEvent(Audio\Sequence\Event::noteOn('G3', 44), 2, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 40), 3, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 38), 0, 14)
;

$oSequencer->allocatePattern('marimba', [2, 6, 10, 14])
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 30), 1, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 32), 2, 1)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 34), 3, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 36), 0, 3)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 38), 1, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 40), 2, 5)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 42), 3, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 44), 0, 7)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 46), 1, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 48), 2, 9)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 50), 3, 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 52), 0, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 55), 1, 12)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 60), 2, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 70), 3, 14)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 0, 15)
;
$oSequencer->allocatePattern('marimba', [3, 7, 11, 15])
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 70), 1, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 68), 2, 1)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 66), 3, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 64), 0, 3)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 62), 1, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 60), 2, 5)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 58), 3, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 56), 0, 7)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 54), 1, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 52), 2, 9)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 50), 3, 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 48), 0, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 46), 1, 12)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 44), 2, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 40), 3, 14)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 38), 0, 15)
;


$oOutput = Audio\Output\Piped::create();
$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
