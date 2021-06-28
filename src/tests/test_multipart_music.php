<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

die("todo - update sequence");

require_once '../PDE.php';

$oBassMachine = new Audio\Machine\TBNaN();
$oBassPattern = new Audio\Sequence\Pattern(1, 256);

$oBassPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('C3', 70), 0, 64 + 1, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 2, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('C3', 70), 0, 64 + 3, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('G1'), 0, 4, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('G2', 70), 0, 64 + 5, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('A#1'), 0, 6, 8);
$oBassPattern->addEvent(new Audio\Sequence\NoteOn('A#2', 70), 0, 64 + 7, 8);

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(0.75);
$oDrumPattern = new Audio\Sequence\Pattern(6, 512);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 0, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 75), 0, 32 + 29, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 3, 2, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 60), 2, 64 + 1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 30), 2, 64 + 3, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('B2'), 5, 32 + 4, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 5, 32 + 31, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 70), 1, 60, 64);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 50), 1, 61, 64);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 4, 63);

//$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 4, 127);


$oChipMachine = new Audio\Machine\ChipTune(4);
$oChipMachine->setInsert(new Audio\Signal\Insert\DelayLoop(null, 575, -0.75, 0.8, 0.2));
$oChipPattern = new Audio\Sequence\Pattern(4, 256);
$oChipMachine->setVoiceMaskWaveform(15, Audio\Signal\IWaveform::SAW);
$oChipMachine->setVoiceMaskEnvelope(3, new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [0.4, 3],
        [0.0, 4]
    ]
));
$oChipMachine->setVoiceMaskEnvelope(12, new Audio\Signal\Envelope\Shape(
    0.0,
    [
        [0.3, 4],
        [0.0, 10]
    ]
));

$oChipMachine->setVoiceMaskVibratoDepth(15, 0.1);

$oChipPattern->addEvent(new Audio\Sequence\NoteOn('Eb4'), 0, 128 + 0, 128);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('E4'),  1, 128 +36, 128);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('C3'),  2, 128 + 8, 128);
$oChipPattern->addEvent(new Audio\Sequence\NoteOn('G3'),  3, 128 + 0, 128);
$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(120)
    ->addMachine('bass', $oBassMachine)
    ->addPattern('bass', $oBassPattern)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('drum', $oDrumPattern)
    ->addMachine('chip', $oChipMachine)
    ->addPattern('chip', $oChipPattern)
;

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer->play($oPCMOut, 256, 6.0);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
