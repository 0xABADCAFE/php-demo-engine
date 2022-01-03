<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;
use ABadCafe\PDE\Audio\Sequence\Event;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(4);

$oBassLine = new Audio\Machine\TBNaN();
$oBassLine
    ->setWaveform(Audio\Signal\IWaveform::SINE)
    ->setResonance(0.0)
    ->setCutoff(1.0)
    ->setLevelTarget(0.75)
    ->setCutoffTarget(1.0)
    ->setOutputLevel(1.0)
;

$oChipTune = new Audio\Machine\ChipTune(4);
$oChipTune
    ->setVoiceMaskWaveform(15, Audio\Signal\IWaveform::SINE)
    ->setOutputLevel(1.0)
;

$oFM2Op = new Audio\Machine\TwoOpFM(4);
$oFM2Op
    ->setOutputLevel(1.0)
;


$oSequencer
    ->addMachine('tbnan', $oBassLine)
    ->allocatePattern('tbnan', [0])
        ->addEvent(Event::noteOn('C4', 127), 0, 0, 0)
        ->addEvent(Event::noteOff('C4', 127), 0, 15, 0)
;

$oSequencer
    ->addMachine('chip', $oChipTune)
    ->allocatePattern('chip', [1])
        ->addEvent(Event::noteOn('C4', 127), 0, 0, 0)
        ->addEvent(Event::noteOff('C4', 127), 0, 15, 0)
;

$oSequencer
    ->addMachine('fm2op', $oFM2Op)
    ->allocatePattern('fm2op', [2])
        ->addEvent(Event::noteOn('C4', 127), 0, 0, 0)
        ->addEvent(Event::noteOff('C4', 127), 0, 15, 0)
;


$oOutput = new Audio\Output\Wav('machine_level.wav');
$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    1.0
);

$oOutput->close();
