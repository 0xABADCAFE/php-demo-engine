<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

die("todo - update sequence");

require_once '../PDE.php';

$sPatch = $_SERVER['argv'][1] ?? 'marimba';

$sPath   = sprintf('machines/multifm/%s.json', $sPatch);
$oPatch  = json_decode(file_get_contents($sPath));

$oDexter = Audio\Machine\Factory::get()
    ->createFrom($oPatch);

//$oDexter->setInsert(new Audio\Signal\Insert\DelayLoop(null, 370.0, 0.5));


$oPattern = new Audio\Sequence\Pattern(6, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 0, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 2, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 4, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 3, 6, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 8, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 2, 10, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 3, 11, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 1, 13, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 14, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 50), 0, 16, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 40), 1, 18, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 50), 2, 20, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3', 30), 3, 22, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 24, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 48), 1, 25, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('G3', 44), 2, 27, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 40), 3, 29, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 38), 0, 30, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 30), 1, 32, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 32), 2, 33, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 34), 3, 34, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 36), 0, 35, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 38), 1, 36, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 40), 2, 37, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 42), 3, 38, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 0, 39, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 46), 1, 40, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 48), 2, 41, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 42, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 52), 0, 43, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 55), 1, 44, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 60), 2, 45, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 70), 3, 46, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 50), 0, 47, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 70), 1, 48, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 68), 2, 49, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 66), 3, 50, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 64), 0, 51, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 62), 1, 52, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 60), 2, 53, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 58), 3, 54, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 56), 0, 55, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 54), 1, 56, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 52), 2, 57, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 50), 3, 58, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C4', 48), 0, 59, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn('D#3', 46), 1, 60, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('F3', 44), 2, 61, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 40), 3, 62, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3', 38), 0, 63, 64);

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(120)
    ->addMachine('fm', $oDexter)
    ->addPattern('fm', $oPattern)

;

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
//$oPCMOut = new Audio\Output\Wav('test_dexter.wav');
$oPCMOut->open();

$fMark = microtime(true);

$oSequencer->play($oPCMOut, 128, 4);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
