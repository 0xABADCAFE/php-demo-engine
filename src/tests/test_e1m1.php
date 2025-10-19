<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);
$oSequencer->setTempo(120);

$oFM = new Audio\Machine\OPHPL(3);
$oFM
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE_SAW)
    ->setModulatorRatio(1.00)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.9)
    )
    ->setModulationIndex(0.27)
    ->setModulatorMix(0.25)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SQUARE)
    ->setCarrierRatio(1.995)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.2)
    )
    ->setPitchLFODepth(0.1)
    ->setPitchLFORate(4.5)
    ->enablePitchLFO(true, true)
    ->setOutputLevel(0.3)
;

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(1.25);

$oSequencer
    ->addMachine('dm', $oDrumMachine)
    ->addMachine('fm', $oFM)
;
// Strum some chords
$oSequencer->allocatePattern('fm', [0, 1])
    ->addEvent(Event::noteOn('E2', 100), 0, 0)
    ->addEvent(Event::noteOn('E2', 100), 1, 1)
    ->addEvent(Event::noteOn('E3', 100), 2, 2)

    ->addEvent(Event::noteOn('E2', 100), 2, 3)
    ->addEvent(Event::noteOn('E2', 100), 1, 4)
    ->addEvent(Event::noteOn('D3', 100), 0, 5)

    ->addEvent(Event::noteOn('E2', 100), 0, 6)
    ->addEvent(Event::noteOn('E2', 100), 1, 7)
    ->addEvent(Event::noteOn('C3', 100), 2, 8)

    ->addEvent(Event::noteOn('E2', 100), 2, 9)
    ->addEvent(Event::noteOn('E2', 100), 1, 10)
    ->addEvent(Event::noteOn('A#2', 100), 0, 11)

    ->addEvent(Event::noteOn('E2', 100), 0, 12)
    ->addEvent(Event::noteOn('E2', 100), 1, 13)
    ->addEvent(Event::noteOn('B2', 100), 2, 14)
    ->addEvent(Event::noteOn('C3', 100), 2, 15)

    ->addEvent(Event::noteOn('E2', 100), 2, 16)
    ->addEvent(Event::noteOn('E2', 100), 1, 17)
    ->addEvent(Event::noteOn('E3', 100), 0, 18)

    ->addEvent(Event::noteOn('E2', 100), 0, 19)
    ->addEvent(Event::noteOn('E2', 100), 1, 20)
    ->addEvent(Event::noteOn('D3', 100), 2, 21)

    ->addEvent(Event::noteOn('E2', 100), 2, 22)
    ->addEvent(Event::noteOn('E2', 100), 1, 23)
    ->addEvent(Event::noteOn('C3', 100), 0, 24)

    ->addEvent(Event::noteOn('E2', 100), 0, 25)
    ->addEvent(Event::noteOn('E2', 100), 1, 26)
    ->addEvent(Event::noteOn('A#2', 100), 2, 27)

;

// Bang some drums
$oSequencer->allocatePattern('dm', [0, 1])
    ->addEvent(Event::noteOn('B5', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('A5', 100), Audio\Machine\TRNaN::KICK, 5, 8)
    ->addEvent(Event::noteOn('A3', 100),  Audio\Machine\TRNaN::SNARE, 2, 4)
    ->addEvent(Event::noteOn('A4', 75),  Audio\Machine\TRNaN::HH_OPEN, 0, 4)

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
