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
$oSequencer->setBeatsPerMeasure(8);
$oSequencer->setTempo(131);


$oElectricGuitar = new ProPHPet(6);
$oElectricGuitar
    // Oscillator 1 config
    ->assignEnumeratedWaveform(IWaveform::SINE_SAW, ProPHPet::TARGET_OSC_1)
    ->setFrequencyRatio(1.00, ProPHPet::TARGET_OSC_1)
    ->setLevel(0.25, ProPHPet::TARGET_OSC_1)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 2.5), ProPHPet::TARGET_OSC_1)

    // Oscillator 2 config
    ->assignEnumeratedWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_2)
    ->setFrequencyRatio(1.995, ProPHPet::TARGET_OSC_2)
    ->setLevel(1.0,  ProPHPet::TARGET_OSC_2)
    ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.7), ProPHPet::TARGET_OSC_2)

    // Modulation
    ->setPhaseModulationIndex(0.3)

    // Filter - Auto wah
    ->setFilterMode(ProPHPet::FILTER_BANDPASS)
    ->setFilterCutoff(0.125)
    ->setFilterResonance(0.3)

    // LFO Config
    ->setLevel(0.05, ProPHPet::TARGET_PITCH_LFO)
    ->setLFORate(4.5, ProPHPet::TARGET_PITCH_LFO)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_1)
    ->enablePitchLFO(ProPHPet::TARGET_OSC_2)
    ->setLevel(0.7, ProPHPet::TARGET_CUTOFF_LFO)
    ->setLFORate(0.25 * 131/60, ProPHPet::TARGET_CUTOFF_LFO)
    ->enableCutoffLFO()
    // Output
    ->setOutputLevel(0.25)
;


$oPerc = new TRNaN;
$oPerc->setOutputLevel(1.25);

$oBassLine = new Audio\Machine\TBNaN();
$oBassLine->setEnumeratedWaveform(Audio\Signal\IWaveform::PULSE);
$oBassLine->setResonance(0.4);
$oBassLine->setCutoff(0.30);
$oBassLine->setOutputLevel(0.75);

$oSequencer
    ->addMachine('sub', $oElectricGuitar)
    ->addMachine('perc', $oPerc)
    ->addMachine('bass', $oBassLine)
;

$oSequencer->allocatePattern('perc', [1])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
;

$oSequencer->allocatePattern('perc', [2])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)
    ->addEvent(Event::noteOn('A4', 55),  Audio\Machine\TRNaN::HH_CLOSED, 2, 4)
    ->addEvent(Event::noteOn('A4', 50),  Audio\Machine\TRNaN::SNARE, 29, 0)
    ->addEvent(Event::noteOn('E4', 50),  Audio\Machine\TRNaN::SNARE, 31, 0)
;

$oSequencer->allocatePattern('perc', [3, 4, 5, 6, 7, 9, 10, 11, 12, 13])
    ->addEvent(Event::noteOn('B3', 100), Audio\Machine\TRNaN::KICK, 0, 4)

    ->addEvent(Event::noteOn('B3', 80),  Audio\Machine\TRNaN::SNARE, 4, 8)
    ->addEvent(Event::noteOn('A4', 70),  Audio\Machine\TRNaN::HH_CLOSED, 0, 8)
    ->addEvent(Event::noteOn('A4', 60),  Audio\Machine\TRNaN::HH_CLOSED, 1, 4)
    ->addEvent(Event::noteOn('A4', 55),  Audio\Machine\TRNaN::HH_OPEN, 2, 4)
    ->addEvent(Event::noteOn('A4', 30),  Audio\Machine\TRNaN::HH_CLOSED, 3, 4)
    ->addEvent(Event::noteOn('A4', 50),  Audio\Machine\TRNaN::SNARE, 29, 0)
    ->addEvent(Event::noteOn('E4', 50),  Audio\Machine\TRNaN::SNARE, 31, 0)
    //->addEvent(Event::noteOn('A4', 50),  Audio\Machine\TRNaN::CLAVE, 11, 16)
    //->addEvent(Event::noteOn('A4', 10),  Audio\Machine\TRNaN::CLAVE, 14, 16)
    ->addEvent(Event::noteOn('A4', 120), Audio\Machine\TRNaN::CLAP, 18, 0)
    ->addEvent(Event::noteOn('A4', 40),  Audio\Machine\TRNaN::COWBELL, 7, 16)
;

$oSequencer->allocatePattern('bass', [1, 2])
    ->addEvent(Event::setCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, 30), 0, 0)
    ->addEvent(Event::noteOn('E3', 40), 0, 0, 8)
    ->addEvent(Event::noteOn('E3', 40), 0, 2, 8)
    ->addEvent(Event::noteOn('B2', 40), 0, 4, 8)
    ->addEvent(Event::noteOn('D3', 40), 0, 6, 8)
;

$oSequencer->allocatePattern('bass', [3, 4, 8, 9, 10, 13])
    ->addEvent(Event::noteOn('E2', 70), 0, 0, 8)
    ->addEvent(Event::noteOn('E3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('E2', 70), 0, 2, 8)
    ->addEvent(Event::noteOn('E3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('B1', 70), 0, 4, 8)
    ->addEvent(Event::noteOn('B2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('D2', 70), 0, 6, 8)
    ->addEvent(Event::noteOn('D3', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [7, 13])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, -2), 0, 1, 1)
    ->addEvent(Event::noteOn('E2', 70), 0, 0, 8)
    ->addEvent(Event::noteOn('E3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('E2', 70), 0, 2, 8)
    ->addEvent(Event::noteOn('E3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('B1', 70), 0, 4, 8)
    ->addEvent(Event::noteOn('B2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('D2', 70), 0, 6, 8)
    ->addEvent(Event::noteOn('D3', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [5, 11])
    ->addEvent(Event::modCtrl(Audio\Machine\TBNaN::CTRL_LPF_CUTOFF, 2), 0, 1, 1)
    ->addEvent(Event::noteOn('G2', 70), 0, 0, 8)
    ->addEvent(Event::noteOn('G3', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('G2', 70), 0, 2, 8)
    ->addEvent(Event::noteOn('G3', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('D2', 70), 0, 4, 8)
    ->addEvent(Event::noteOn('D3', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('F2', 70), 0, 6, 8)
    ->addEvent(Event::noteOn('F3', 40), 0, 7, 8)
;

$oSequencer->allocatePattern('bass', [6, 12])
    ->addEvent(Event::noteOn('A1', 70), 0, 0, 8)
    ->addEvent(Event::noteOn('A2', 40), 0, 1, 8)
    ->addEvent(Event::noteOn('A1', 70), 0, 2, 8)
    ->addEvent(Event::noteOn('A2', 40), 0, 3, 8)
    ->addEvent(Event::noteOn('E1', 70), 0, 4, 8)
    ->addEvent(Event::noteOn('E2', 40), 0, 5, 8)
    ->addEvent(Event::noteOn('G1', 70), 0, 6, 8)
    ->addEvent(Event::noteOn('G2', 40), 0, 7, 8)
;


// Strum some chords
$oSequencer->allocatePattern('sub', [0, 3, 4, 7])
    ->addEvent(Event::noteOn('E2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 90), 1, 0, 0)
    ->addEvent(Event::noteOn('E3', 80), 2, 1, 0)
    ->addEvent(Event::noteOn('G#3', 45), 3, 1, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 2, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 2, 0)
;

$oSequencer->allocatePattern('sub', [1, 5])
    ->addEvent(Event::noteOn('G2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('B2', 40), 1, 0, 0)
    ->addEvent(Event::noteOn('D3', 80), 2, 1, 0)
    ->addEvent(Event::noteOn('G3', 75), 3, 1, 0)
    ->addEvent(Event::noteOn('B3', 70), 4, 2, 0)
    ->addEvent(Event::noteOn('G4', 70), 5, 2, 0)
;
$oSequencer->allocatePattern('sub', [2, 6])
    ->addEvent(Event::noteOn('A2', 100), 0, 0, 0)
    ->addEvent(Event::noteOn('A2', 90), 1, 0, 0)
    ->addEvent(Event::noteOn('G3', 80), 2, 1, 0)
    ->addEvent(Event::noteOn('B3', 75), 3, 1, 0)

    ->addEvent(Event::setNote('C#3'), 3, 16)

    ->addEvent(Event::noteOn('E4', 70), 4, 2, 0)
    ->addEvent(Event::noteOn('E4', 70), 5, 2, 0)
    ->addEvent(Event::noteOn('G2', 30), 0, 28)
;



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
