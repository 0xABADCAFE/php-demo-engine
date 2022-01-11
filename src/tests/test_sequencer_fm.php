<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Machine\Control\IAutomatable;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);

$oFM = new Audio\Machine\TwoOpFM(5);
$oFM
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE_HALF_RECT)
    ->setModulatorRatio(1)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 0.2],
                [0.0, 10.0]
            ]
        )
    )
    ->setModulationIndex(0.25)
    ->setModulatorMix(0.0)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 0.1],
                [0.0, 12.0]
            ]
        )
    )
    ->setCarrierMix(0)
    ->setOutputLevel(1)
    ->setPitchLFODepth(0.03)
    ->setPitchLFORate(4.5)
    ->enablePitchLFO(true, true)
;


$oSequencer
    ->addMachine('fm', $oFM)
;

$oSequencer->allocatePattern('fm', [0])
    ->addEvent(Event::setCtrl(Audio\Machine\TwoOpFM::CTRL_MODULATOR_RATIO, 15), 0, 0)
    ->addEvent(Event::setCtrl(Audio\Machine\TwoOpFM::CTRL_MODULATOR_DETUNE, 0), 0, 0)
    ->addEvent(Event::setCtrl(Audio\Machine\TwoOpFM::CTRL_CARRIER_RATIO, 15), 0, 0)
    //->addEvent(Event::modCtrl(Audio\Machine\TwoOpFM::CTRL_MODULATOR_RATIO, 8), 0, 4, 4)
    //->addEvent(Event::modCtrl(Audio\Machine\TwoOpFM::CTRL_MODULATOR_DETUNE, 32), 0, 4, 1)

    ->addEvent(Event::noteOn('C2', 50), 4, 0)
    ->addEvent(Event::noteOn('C4', 50), 0, 0)
    ->addEvent(Event::noteOn('E4', 50), 1, 0)
    ->addEvent(Event::noteOn('G4', 50), 2, 0)
    ->addEvent(Event::noteOn('C5', 50), 3, 0)
;


$oSequencer->allocatePattern('fm', [1])
    ->addEvent(Event::setNote('D#4'), 1, 0)
;

if (!empty($_SERVER['argv'][2])) {
    $oOutput = new Audio\Output\Wav($_SERVER['argv'][2] . '.wav');
} else {
    $oOutput = Audio\Output\Piped::create();
}

$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
