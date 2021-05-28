<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';


$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumPattern = new Audio\Sequence\Pattern(6, 32);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 1, 4);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 2, 8);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 3, 12);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 4, 16);
$oDrumPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 5, 20);


$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(100)
    ->addMachine('drum', $oDrumMachine)
    ->addPattern('drum', $oDrumPattern);
;

// Open the audio
$oPCMOut = new Audio\Output\APlay();
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer->play($oPCMOut, 32);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
