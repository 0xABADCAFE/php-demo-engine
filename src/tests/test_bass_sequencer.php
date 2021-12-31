<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);


$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(1.0);

$oBassLine = new Audio\Machine\TBNaN();
$oBassLine->setResonance(0.4);
$oBassLine->setCutoff(0.30);
//$oBassLine->setInsert(new Audio\Signal\Insert\DelayLoop(null, 125, 0.5));

$oSequencer
    ->addMachine('drums', $oDrumMachine)
    ->addMachine('bass', $oBassLine)
;


$oSequencer->allocatePattern('drums', [0, 1, 2, 3, 4, 5, 6, 7])
    ->addEvent(Event::noteOn('B2', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('B3', 80),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 70),  Audio\Machine\TRNaN::HH_CLOSED, 0, 8)
    ->addEvent(Event::noteOn('A4', 60),  Audio\Machine\TRNaN::HH_CLOSED, 1, 4)
    ->addEvent(Event::noteOn('A4', 50),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)
    ->addEvent(Event::noteOn('A4', 30),  Audio\Machine\TRNaN::HH_CLOSED, 3, 4)
    ->addEvent(Event::noteOn('A4', 50),  Audio\Machine\TRNaN::SNARE, 29, 0)
    ->addEvent(Event::noteOn('E4', 50),  Audio\Machine\TRNaN::SNARE, 31, 0)
;

$oSequencer->allocatePattern('bass', [0])
    ->addEvent(Event::setCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, 20), 0, 0)
    ->addEvent(Event::setCtrl(Audio\Machine\TBNaN::CTRL_LPF_RESONANCE, 20), 0, 0)

    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, 2), 0, 1, 1)

    ->addEvent(Event::noteOn('C2', 60), 0, 0, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('C2', 60), 0, 2, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('G1', 60), 0, 4, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('A#1', 60), 0, 6, 8)
    ->addEvent(Event::noteOn('A#2', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [1])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_RESONANCE, 10), 0, 0, 1)
    ->addEvent(Event::noteOn('C2', 60), 0, 0, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('C2', 60), 0, 2, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('G1', 60), 0, 4, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('A#1', 60), 0, 6, 8)
    ->addEvent(Event::noteOn('A#2', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [2])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, -2), 0, 0, 1)
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_RESONANCE, -2), 0, 0, 1)
    ->addEvent(Event::noteOn('C2', 60), 0, 0, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('C2', 60), 0, 2, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('G1', 60), 0, 4, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('A#1', 60), 0, 6, 8)
    ->addEvent(Event::noteOn('A#2', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [3])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, 2), 0, 0, 1)
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_RESONANCE, -1), 0, 0, 1)

    ->addEvent(Event::noteOn('C2', 60), 0, 0, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('C2', 60), 0, 2, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('G1', 60), 0, 4, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('A#1', 60), 0, 6, 8)
    ->addEvent(Event::noteOn('A#2', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [4, 5, 6, 7])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, -1), 0, 0, 2)
    ->addEvent(Event::noteOn('C2', 60), 0, 0, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('C2', 60), 0, 2, 8)
    ->addEvent(Event::noteOn('C3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('G1', 60), 0, 4, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('A#1', 60), 0, 6, 8)
    ->addEvent(Event::noteOn('A#2', 40), 0, 7, 8)
;


if (!empty($_SERVER['argv'][1])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][1] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
