<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

die("todo - update sequence");

require_once '../PDE.php';


$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumPattern = new Audio\Sequence\Pattern(6, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 0, 0);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 2, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 3, 12);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 4, 16);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 5, 20);


$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(100)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('drum', $oDrumPattern);
;

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer->play($oPCMOut, 32, 6);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
