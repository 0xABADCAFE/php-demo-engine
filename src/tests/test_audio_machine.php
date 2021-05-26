<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oChipMachine = new Audio\Machine\ChipTune(3);

$oChipMachine
    ->setVoiceMaskWaveform(1, Audio\Machine\ChipTune::SQUARE)
    ->setVoiceMaskWaveform(6, Audio\Machine\ChipTune::TRIANGLE)
    ->setVoiceMaskEnvelope(1, new Audio\Signal\Envelope\DecayPulse(0.75, 0.1))
    ->setVoiceMaskEnvelope(6, new Audio\Signal\Envelope\Shape(0.0, [[1.0, 0.5], [0.5, 1.0]]))
    ->setVoiceLevel(0, 1.25)
    ->setVoiceLevel(1, 0.33)
    ->setVoiceLevel(2, 0.33)
    ->setOutputLevel(0.8)
;

$oChipPattern = new Audio\Sequence\Pattern(3, 64);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C3'), 0, 2, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C3'), 0, 3, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('G1'), 0, 16, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('G2'), 0, 16+2, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('G2'), 0, 16+3, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('Bb1'), 0, 32, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('Bb2'), 0, 32+2, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('Bb2'), 0, 32+3, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('F1'), 0, 48, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('F2'), 0, 48+2, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('F2'), 0, 48+3, 4);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C4'), 1, 0);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('D4'), 1, 16);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('F4'), 1, 48);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('E4'), 2, 0);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('B3'), 2, 16);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('F4'), 2, 32);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C4'), 2, 48);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('G4'), 1, 60);

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumPattern = new Audio\Sequence\Pattern(6, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 29);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 2, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 2, 1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 3, 2, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 1, 4, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 1, 31, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 4, 15, 32);

$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 5, 10, 16);

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->addMachine('chip', $oChipMachine)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('chip', $oChipPattern)
    ->addPattern('drum', $oDrumPattern);
;

// Open the audio
$oPCMOut = new Audio\Output\Wav('machine.wav');
$oPCMOut->open();

$fMark = microtime(true);
$oSequencer->play($oPCMOut);
$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
