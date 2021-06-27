<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

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
$oFMMarimba->setInsert(new Audio\Signal\Insert\DelayLoop(null, 124.0 * 3, -0.6));


$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->addMachine('marimba', $oFMMarimba);
$oPattern = $oSequencer->allocatePattern('marimba', 1, [0, 4, 8, 12]);

$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 0, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 2, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 4, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 3, 6, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 8, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 2, 10, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 3, 11, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 13, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 14, 64);

$oPattern = $oSequencer->allocatePattern('marimba', 1, [1, 5, 9, 13]);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 16-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 1, 18-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 50), 2, 20-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 3, 22-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 24-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 25-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('G3', 44), 2, 27-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 40), 3, 29-16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 38), 0, 30-16, 64);

$oPattern = $oSequencer->allocatePattern('marimba', 1, [2, 6, 10, 14]);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 30), 1, 32-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 32), 2, 33-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 34), 3, 34-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 36), 0, 35-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 38), 1, 36-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 37-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 42), 3, 38-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 0, 39-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 46), 1, 40-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 48), 2, 41-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 42-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 52), 0, 43-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 55), 1, 44-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 60), 2, 45-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 70), 3, 46-32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 47-32, 64);

$oPattern = $oSequencer->allocatePattern('marimba', 1, [3, 7, 11, 15]);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 70), 1, 48-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 68), 2, 49-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 66), 3, 50-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 64), 0, 51-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 62), 1, 52-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 53-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 58), 3, 54-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 56), 0, 55-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 54), 1, 56-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 52), 2, 57-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 58-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 48), 0, 59-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 46), 1, 60-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 2, 61-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 40), 3, 62-48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 38), 0, 63-48, 64);

$oOutput = Audio\Output\Piped::create();
$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
