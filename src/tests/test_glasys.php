<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Signal\IWaveform;
use ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio\Machine\ProPHPet;
use ABadCafe\PDE\Audio\Machine\TRNaN;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(4);
$oSequencer->setTempo(120);


$oMonoBass = new ProPHPet(1);
$oMonoBass
    // Oscillator 1 config
    ->assignEnumeratedWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_1)
    ->setFrequencyRatio(0.251, ProPHPet::TARGET_OSC_1)
    ->setLevel(0.33, ProPHPet::TARGET_OSC_1)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 2.5), ProPHPet::TARGET_OSC_1)

    // Oscillator 2 config
    ->assignEnumeratedWaveform(IWaveform::SAW, ProPHPet::TARGET_OSC_2)
    ->setFrequencyRatio(0.997, ProPHPet::TARGET_OSC_2)
    ->setLevel(0.5,  ProPHPet::TARGET_OSC_2)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 2.5), ProPHPet::TARGET_OSC_2)

    // Modulation
    ->setPhaseModulationIndex(0.02)
    ->setFilterMode(ProPHPet::FILTER_LOWPASS)
    ->setFilterCutoff(0.52)
    ->setFilterResonance(0.1)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.5), ProPHPet::TARGET_CUTOFF)

    ->setLevel(0.15, ProPHPet::TARGET_PITCH_LFO)
    ->setLFORate(2.5, ProPHPet::TARGET_PITCH_LFO)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_1)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_2)

    // Output
    ->setOutputLevel(0.5)
    ->setInsert(new Audio\Signal\Insert\DelayLoop(null, 122.0 * 4, 0.4))

;

$oPerc = new TRNaN;
$oPerc->setOutputLevel(1);


$oSequencer
    ->addMachine('perc', $oPerc)
    ->addMachine('sub', $oMonoBass)
;

$oSequencer->allocatePattern('sub', [0])
    ->addEvent(Event::noteOn('E4', 100), 0, 12, 0)
    ->addEvent(Event::noteOn('B3', 100), 0, 13, 0)
    ->addEvent(Event::noteOn('G#3', 100), 0, 14, 0)
    ->addEvent(Event::noteOn('E3', 100), 0, 15, 0)
;

$oSequencer->allocatePattern('sub', [1])
    ->addEvent(Event::noteOn('Eb3', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 2, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 4, 0)
    ->addEvent(Event::noteOn('F#3', 100), 0, 7, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 9, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 12, 0)
    ->addEvent(Event::noteOn('F3', 100), 0, 15, 0)
;

$oSequencer->allocatePattern('sub', [3])
    ->addEvent(Event::noteOn('Eb4', 100), 0, 2, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 4, 0)
    ->addEvent(Event::noteOn('F#3', 100), 0, 7, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 10, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 12, 0)
    ->addEvent(Event::noteOn('F3', 100), 0, 15, 0)
;

$oSequencer->allocatePattern('sub', [2,4])
    ->addEvent(Event::noteOn('Eb4', 100), 0, 2, 0)
    ->addEvent(Event::noteOn('Eb4', 100), 0, 4, 0)
    ->addEvent(Event::noteOn('E3', 100), 0, 7, 0)
    ->addEvent(Event::noteOn('C#4', 100), 0, 10, 0)
    ->addEvent(Event::noteOn('C#4', 100), 0, 12, 0)
    ->addEvent(Event::noteOn('Eb3', 100), 0, 15, 0)
;

$oSequencer->allocatePattern('sub', [5,6])
    //->addEvent(Event::noteOn('Eb3', 100), 0, 0, 0)
;

$oSequencer->allocatePattern('perc',[1,3])
    ->addEvent(Event::noteOn('A1', 70), Audio\Machine\TRNaN::HH_OPEN, 0, 0)
    ->addEvent(Event::noteOn('C1', 70), Audio\Machine\TRNaN::HH_CLOSED, 1, 4)
    ->addEvent(Event::noteOn('A1', 70), Audio\Machine\TRNaN::HH_CLOSED, 2, 4)
    ->addEvent(Event::noteOn('A3', 70), Audio\Machine\TRNaN::HH_CLOSED, 3, 4)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 0, 0)
    ->addEvent(Event::noteOn('B2', 50), Audio\Machine\TRNaN::KICK, 3, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 4)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 6)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::HH_OPEN, 7, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 7, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 10, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 11, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 12, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 14, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 15, 0)
;

$oSequencer->allocatePattern('perc',[2,4])
    ->addEvent(Event::noteOn('C1', 70), Audio\Machine\TRNaN::HH_CLOSED, 1, 4)
    ->addEvent(Event::noteOn('A1', 70), Audio\Machine\TRNaN::HH_CLOSED, 2, 4)
    ->addEvent(Event::noteOn('A3', 70), Audio\Machine\TRNaN::HH_CLOSED, 3, 4)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 0, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 3, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 4)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 6)
    ->addEvent(Event::noteOn('B2', 80), Audio\Machine\TRNaN::KICK, 7, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 11, 0)
    ->addEvent(Event::noteOn('A5', 80), Audio\Machine\TRNaN::SNARE, 12, 0)
    ->addEvent(Event::noteOn('A3', 80), Audio\Machine\TRNaN::TOM, 13, 0)
    ->addEvent(Event::noteOn('G3', 80), Audio\Machine\TRNaN::TOM, 14, 0)
    ->addEvent(Event::noteOn('E3', 80), Audio\Machine\TRNaN::TOM, 15, 0)

;

$oSequencer->allocatePattern('perc',[5])
    ->addEvent(Event::noteOn('A5', 70), Audio\Machine\TRNaN::HH_OPEN, 0, 0)
    ->addEvent(Event::noteOn('B2', 70), Audio\Machine\TRNaN::KICK, 0, 0)
;

if (!empty($_SERVER['argv'][1])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][1] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    5.0,
    0,
    0,
    11
);

$oOutput->close();

Audio\Signal\Oscillator\Base::printPacketStats();
