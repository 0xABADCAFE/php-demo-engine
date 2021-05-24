<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oChipMachine = new Audio\Machine\ChipTune(4);


$oChipPattern = new Audio\Sequence\Pattern(4, 64);
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
$oDrumPattern = new Audio\Sequence\Pattern(1, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 29);

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->addMachine('chip', $oChipMachine)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('chip', $oChipPattern)
    ->addPattern('drum', $oDrumPattern);

// Open the audio
$oPCMOut = new Audio\Output\Wav('seq.wav');
$oPCMOut->open();

$oSequencer->play($oPCMOut, 10000);

$oPCMOut->close();
