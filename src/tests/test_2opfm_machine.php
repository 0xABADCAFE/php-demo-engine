<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oFMMarimba = new Audio\Machine\TwoOpFM(4);

$oFMMarimba
    ->setModulatorWaveform(Audio\Signal\IWaveform::TRIANGLE)
    ->setModulatorRatio(7.01)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.015)
    )
    ->setModulationIndex(0.2)
    ->setModulatorMix(0.15)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1.99)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.1)
    )
    ->setOutputLevel(0.3);
;

$oFMPad = new Audio\Machine\TwoOpFM(5);
$oFMPad
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE_HALF_RECT)
    ->setModulatorRatio(2.001)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 3.0],
                [0.0, 10.0]
            ]
        )
    )
    ->setModulationIndex(0.5)
    ->setModulatorMix(0.2)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(3.999)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 1.00],
                [0.0, 12.0]
            ]
        )
    )
    ->setCarrierMix(0.5)
    ->setOutputLevel(0.05);
;

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(1.25);
$oDrumPattern = new Audio\Sequence\Pattern(6, 256);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 0, 32, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 3, 64+2, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 5, 64+25, 32);

$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 60), 2, 64+1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 30), 2, 64+3, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('B2'), 5, 96 + 4, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 5, 32 + 31, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 70), 1, 60);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 50), 1, 61);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 4, 63);


$oMarimbaPattern = new Audio\Sequence\Pattern(4, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 0, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 2, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 4, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 3, 6, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 8, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 2, 10, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 3, 11, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 13, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 14, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 16, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 1, 18, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 50), 2, 20, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 3, 22, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 24, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 25, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('G3', 44), 2, 27, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 40), 3, 29, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 38), 0, 30, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 30), 1, 32, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 32), 2, 33, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 34), 3, 34, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 36), 0, 35, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 38), 1, 36, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 37, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 42), 3, 38, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 0, 39, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 46), 1, 40, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 48), 2, 41, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 42, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 52), 0, 43, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 55), 1, 44, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 60), 2, 45, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 70), 3, 46, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 47, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 70), 1, 48, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 68), 2, 49, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 66), 3, 50, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 64), 0, 51, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 62), 1, 52, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 53, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 58), 3, 54, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 56), 0, 55, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 54), 1, 56, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 52), 2, 57, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 58, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('C4', 48), 0, 59, 64);

$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 46), 1, 60, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 2, 61, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 40), 3, 62, 64);
$oMarimbaPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 38), 0, 63, 64);

$oFMMarimba->setInsert(new Audio\Signal\Insert\DelayLoop(null, 370.0, 0.5));

$oFMPadPattern = new Audio\Sequence\Pattern(5, 256);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 64 + 0, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\SetNote('F1'), 0, 64 + 32, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('E3'), 1, 128 + 1, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\SetNote('D#3'), 1, 128 + 32, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('G3'), 2, 128 + 2, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('C3'), 3, 128 + 3, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\SetNote('A#3'), 3, 128 + 32, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('C4'), 4, 128+64 + 0, 64);
$oFMPadPattern->addEvent(new Audio\Sequence\NoteOn('D#4'), 4, 128+64 + 32, 64);

$oFMPad->setInsert(new Audio\Signal\Insert\DelayLoop(null, 250.0, 0.5));


$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(120)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('drum', $oDrumPattern)
    ->addMachine('pad', $oFMPad)
    ->addPattern('pad', $oFMPadPattern)
    ->addMachine('marimba', $oFMMarimba)
    ->addPattern('marimba', $oMarimbaPattern)

;

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
//$oPCMOut = new Audio\Output\Wav('test_fm.wav');
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer->play($oPCMOut, 256, 4);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
