<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(0.75);
$oDrumPattern = new Audio\Sequence\Pattern(6, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 0, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('G#4'), 0, 29, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4'), 1, 4, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('B3', 40), 5, 3, 16);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 40), 5, 12, 16);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 50), 3, 2, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 50), 2, 0, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 30), 2, 1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('A4', 50), 4, 6, 16);

//$oDrumMachine->setInsert(new Audio\Signal\Insert\DelayLoop(null));


// Open the audio
$oPCMOut = Audio\Output\Piped::create();
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(120)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('drum', $oDrumPattern)

    ->play($oPCMOut, 256, 4.0)
;

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
