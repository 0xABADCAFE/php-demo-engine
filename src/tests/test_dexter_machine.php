<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

die("todo - update sequence");

require_once '../PDE.php';

$oDexter = new Audio\Machine\DeXter(6, 3);

$oDexter
    ->aliasOperator(2, 'tine')
    ->aliasOperator(1, 'bar')
    ->aliasOperator(0, 'body')

    // Tine - basic sinewave, fast decay, 18x fundamental + slight detune
    //        This operator is a pure modulator
    ->selectOperatorName('tine')
        ->setRatio(19.001)
        ->setLevelEnvelope(
            new Audio\Signal\Envelope\DecayPulse(1.0, 0.075) // fast decay
        )

    // Bar - basic sinewave, slow decay, 2x fundamental + slight detune down
    //       This operator is a modulator, but also mixes to output
    ->selectOperatorName('bar')
        ->setRatio(1.995)
        ->setLevelEnvelope(
            new Audio\Signal\Envelope\DecayPulse(1.0, 0.75) // slower decay
        )
        ->setOutputMixLevel(0.25) // Slight contribution to overall output

    // Body - basic sinewave, slowest decay, 1x fundamental + slight detune up
    //        This operator is the main carrier.
    ->selectOperatorName('body')
        ->setRatio(1.005)
        ->setLevelEnvelope(
            new Audio\Signal\Envelope\DecayPulse(1.0, 1.0)
        )
        ->setModulation(2, 0.05)  // Tine modulation
        ->setModulation(1, 0.1)   // Bar modulation
        ->setOutputMixLevel(0.75) // Drop contribution to output to compensate for op 1
;

$oPattern = new Audio\Sequence\Pattern(6, 64);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C2'), 0, 0);
$oPattern->addEvent(new Audio\Sequence\NoteOn('C3'), 1, 1);
$oPattern->addEvent(new Audio\Sequence\NoteOn('E3'), 2, 2);
$oPattern->addEvent(new Audio\Sequence\NoteOn('G3'), 3, 3);
$oPattern->addEvent(new Audio\Sequence\NoteOn('A#3'), 4, 4);
$oPattern->addEvent(new Audio\Sequence\NoteOn('D4'), 5, 5);

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

$oSequencer->play($oPCMOut, 64, 1);

$fElapsed = microtime(true) - $fMark;

$oPCMOut->close();

printf("\nElapsed time %.3f seconds\n", $fElapsed);
