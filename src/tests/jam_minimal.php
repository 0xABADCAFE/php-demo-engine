<?php

/**
 * Minimal JAM
 */

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Machine\Control\IAutomatable;

use ABadCafe\PDE\Audio\Signal\IWaveform;
use ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio\Machine\ProPHPet;
use ABadCafe\PDE\Audio\Machine\TRNaN;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer
    ->setTempo(120)
    ->setBeatsPerMeasure(8)
    ->setSwing(1.2, 1)
;

$oDrumMachine = new TRNaN();
$oDrumMachine->setOutputLevel(1.0);

$oSubSynth = new ProPHPet(2);
$oSubSynth
    // Oscillator 1 config
    ->assignEnumeratedWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_1)
    ->setFrequencyRatio(1.0001, ProPHPet::TARGET_OSC_1)
    ->setLevel(0.1, ProPHPet::TARGET_OSC_1)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.1), ProPHPet::TARGET_OSC_1)

    // Oscillator 2 config
    ->assignEnumeratedWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_2)
    ->setFrequencyRatio(1.9999, ProPHPet::TARGET_OSC_2)
    ->setLevel(1.0,  ProPHPet::TARGET_OSC_2)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.15), ProPHPet::TARGET_OSC_2)

    // Modulation
    ->setPhaseModulationIndex(0.17)

    // Filter
    ->setFilterMode(ProPHPet::FILTER_LOWPASS)
    ->setFilterCutoff(0.10)
    ->setFilterResonance(0.15)

    // LFO
    ->setLevel(0.75, ProPHPet::TARGET_CUTOFF_LFO)
    ->setLFORate(0.125 * 90/60, ProPHPet::TARGET_CUTOFF_LFO)
    ->enableCutoffLFO()

    // FX
    ->setInsert(new Audio\Signal\Insert\DelayLoop(null, 123.0 * 8, 0.3))
;


$oSequencer
    ->addMachine('drums', $oDrumMachine)
    ->addMachine('bass', $oSubSynth)
;

$oSequencer->allocatePattern('bass', [0, 2, 4, 6, 8, 10, 12, 14])
    ->addEvent(Event::noteOn('D2', 40), 0, 0, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 1, 0)
    ->addEvent(Event::noteOn('D3', 40), 0, 1, 16)
    ->addEvent(Event::noteOn('D2', 40), 0, 2, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 3, 0)
    ->addEvent(Event::noteOn('F2', 40), 0, 4, 16)
    ->addEvent(Event::noteOn('D2', 40), 0, 6, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 7, 0)
    ->addEvent(Event::noteOn('G2', 40), 0, 8, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 9, 0)
    ->addEvent(Event::noteOn('D2', 40), 0, 10, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 11, 0)
    ->addEvent(Event::noteOn('A2', 40), 0, 11, 16)
    ->addEvent(Event::noteOn('F1', 40), 1, 13, 0)
    ->addEvent(Event::noteOn('F2', 40), 0, 13, 16)
    ->addEvent(Event::noteOn('C3', 40), 0, 14, 16)
    ->addEvent(Event::noteOn('D3', 40), 0, 15, 16)
    ->addEvent(Event::noteOn('G1', 40), 1, 16+1, 0)
    ->addEvent(Event::noteOn('G1', 40), 1, 16+3, 0)
    ->addEvent(Event::noteOn('G1', 40), 1, 16+7, 0)
    ->addEvent(Event::noteOn('G1', 40), 1, 16+9, 0)
    ->addEvent(Event::noteOn('G1', 40), 1, 16+11, 0)
;


$oSequencer->allocatePattern('bass', [1, 3, 5, 7, 9, 11, 13, 15])
    ->addEvent(Event::noteOn('D2', 40), 0, 0, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 1, 0)
    ->addEvent(Event::noteOn('D3', 40), 0, 1, 16)
    ->addEvent(Event::noteOn('D2', 40), 0, 2, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 3, 0)
    ->addEvent(Event::noteOn('F2', 40), 0, 4, 16)
    ->addEvent(Event::noteOn('D2', 40), 0, 6, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 7, 0)
    ->addEvent(Event::noteOn('G2', 40), 0, 8, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 9, 0)
    ->addEvent(Event::noteOn('D2', 40), 0, 10, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 11, 0)
    ->addEvent(Event::noteOn('A2', 40), 0, 11, 16)
    ->addEvent(Event::noteOn('C1', 40), 1, 13, 0)
    ->addEvent(Event::noteOn('F2', 40), 0, 13, 16)
    ->addEvent(Event::noteOn('C3', 40), 0, 14, 16)
    ->addEvent(Event::noteOn('D3', 40), 0, 15, 16)
    ->addEvent(Event::noteOn('D1', 40), 1, 16+1, 0)
    ->addEvent(Event::noteOn('D1', 40), 1, 16+3, 0)
    ->addEvent(Event::noteOn('D1', 40), 1, 16+7, 0)
    ->addEvent(Event::noteOn('D1', 40), 1, 16+9, 0)
    ->addEvent(Event::noteOn('D1', 40), 1, 16+11, 0)
;

$oSequencer->allocatePattern('drums', [2, 3, 4])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
;

$oSequencer->allocatePattern('drums', [5])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 0)
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 4, 0)
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 8, 0)
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 12, 0)

    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 16+0, 0)
    ->addEvent(Event::noteOn('B3', 90),  Audio\Machine\TRNaN::KICK, 16+3, 0)
    ->addEvent(Event::noteOn('B3', 85),  Audio\Machine\TRNaN::KICK, 16+6, 0)
    ->addEvent(Event::noteOn('B3', 80),  Audio\Machine\TRNaN::KICK, 16+9, 0)
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 16+12, 0)

    ->addEvent(Event::noteOn('A4', 10),  Audio\Machine\TRNaN::HH_CLOSED, 2)
    ->addEvent(Event::noteOn('A4', 15),  Audio\Machine\TRNaN::HH_CLOSED, 6)
    ->addEvent(Event::noteOn('A4', 20),  Audio\Machine\TRNaN::HH_CLOSED, 10)
    ->addEvent(Event::noteOn('A4', 30),  Audio\Machine\TRNaN::HH_OPEN, 14)

    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 18+0)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_CLOSED, 18+3)
    ->addEvent(Event::noteOn('A4', 30),  Audio\Machine\TRNaN::HH_CLOSED, 18+6)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 18+9)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_CLOSED, 18+11)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 16+13)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_OPEN,   16+14)
    ->addEvent(Event::noteOn('B3', 70),  Audio\Machine\TRNaN::SNARE,     16+12)
    ->addEvent(Event::noteOn('A2', 60),  Audio\Machine\TRNaN::CLAP,      16+11)
    ->addEvent(Event::noteOn('A2', 80),  Audio\Machine\TRNaN::CLAP,      16+12)
;

$oSequencer->allocatePattern('drums', [6,7])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('B3', 75),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)
    ->addEvent(Event::noteOn('A3', 60),  Audio\Machine\TRNaN::CLAP, 27)
;

$oSequencer->allocatePattern('drums', [8,9,12])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('B3', 75),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 1, 2)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)
    ->addEvent(Event::noteOn('A3', 60),  Audio\Machine\TRNaN::CLAP, 27)
;

$oSequencer->allocatePattern('drums', [13])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('B3', 75),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 1, 2)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)
    ->addEvent(Event::noteOn('A3', 60),  Audio\Machine\TRNaN::CLAP, 27)
    ->addEvent(Event::noteOn('B3', 50),  Audio\Machine\TRNaN::SNARE, 30)
    ->addEvent(Event::noteOn('B2', 30),  Audio\Machine\TRNaN::SNARE, 31)

;


$oSequencer->allocatePattern('drums', [10,11,14,15])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('B3', 75),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::HH_CLOSED, 1, 2)
    ->addEvent(Event::noteOn('A4', 45),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)

    ->addEvent(Event::noteOn('B4', 20),  Audio\Machine\TRNaN::COWBELL, 1, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 2, 0)
    ->addEvent(Event::noteOn('B4', 60),  Audio\Machine\TRNaN::COWBELL, 4, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 8, 0)
    ->addEvent(Event::noteOn('B4', 20),  Audio\Machine\TRNaN::COWBELL, 11, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 13, 0)
    ->addEvent(Event::noteOn('B4', 40),  Audio\Machine\TRNaN::COWBELL, 14, 0)

    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 17, 0)
    ->addEvent(Event::noteOn('B4', 50),  Audio\Machine\TRNaN::COWBELL, 18, 0)
    ->addEvent(Event::noteOn('B4', 40),  Audio\Machine\TRNaN::COWBELL, 20, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 23, 0)
    ->addEvent(Event::noteOn('B4', 40),  Audio\Machine\TRNaN::COWBELL, 24, 0)
    ->addEvent(Event::noteOn('B4', 50),  Audio\Machine\TRNaN::COWBELL, 25, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 27, 0)
    ->addEvent(Event::noteOn('B4', 40),  Audio\Machine\TRNaN::COWBELL, 29, 0)
    ->addEvent(Event::noteOn('B4', 30),  Audio\Machine\TRNaN::COWBELL, 30, 0)
;

$oSequencer->allocatePattern('drums', [16]);


if (!empty($_SERVER['argv'][1])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][1] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    5.0
);

$oOutput->close();

Audio\Signal\Oscillator\Base::printPacketStats();
