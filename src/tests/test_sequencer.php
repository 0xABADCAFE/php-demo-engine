<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oSequencer = new Audio\Machine\Sequencer();
$oSequencer->setBeatsPerMeasure(8);


// $oFMMarimba = new Audio\Machine\TwoOpFM(4);
// $oFMMarimba
//     ->setModulatorWaveform(Audio\Signal\IWaveform::SINE)
//     ->setModulatorRatio(7.01)
//     ->setModulatorLevelEnvelope(
//         new Audio\Signal\Envelope\DecayPulse(1.0, 0.016)
//     )
//     ->setModulationIndex(0.2)
//     ->setModulatorMix(0.15)
//     ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
//     ->setCarrierRatio(1.99)
//     ->setCarrierLevelEnvelope(
//         new Audio\Signal\Envelope\DecayPulse(1.0, 0.1)
//     )
//     ->setOutputLevel(0.35)
// ;
//
//
// $oFMMarimba->setInsert(new Audio\Signal\Insert\DelayLoop(null, 123.0 * 3, 0.6));

$oDrumMachine = new Audio\Machine\TRNaN();
$oDrumMachine->setOutputLevel(1.25);
// $oBassLine = Audio\Machine\Factory::get()
//     ->createFrom(json_decode(file_get_contents('machines/multifm/recently.json')))
//     ->setOutputLevel(0.35);

$oChipMachine = new Audio\Machine\ChipTune(3);
$oChipMachine->setVoiceMaskEnvelope(3, new Audio\Signal\Envelope\Shape(
    0.6,
    [
        [0.0, 0.2]
    ]
));

$oChipMachine->setVoiceMaskEnvelope(4, new Audio\Signal\Envelope\Shape(
    0.6,
    [
        [0.0, 0.5]
    ]
));


$oChipMachine->setVoiceMaskWaveform(3, Audio\Signal\IWaveform::PULSE);
$oChipMachine->setVoiceMaskWaveform(4, Audio\Signal\IWaveform::TRIANGLE);

$oChipMachine->setVoiceMaskVibratoRate(15, 6.0);
$oChipMachine->setVoiceMaskVibratoDepth(15, 0.1);
$oChipMachine->setOutputLevel(0.5);
$oChipMachine->setInsert(new Audio\Signal\Insert\DelayLoop(null, 123.0 * 3, 0.6));

$oBassLine = new Audio\Machine\TBNaN();

$oFMPad = new Audio\Machine\TwoOpFM(4);
$oFMPad
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE, Audio\Signal\Waveform\Rectifier::FULL_RECT_P)
    ->setModulatorRatio(1.01)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 3.0],
                [0.0, 10.0]
            ]
        )
    )
    ->setModulationIndex(0.5)
    ->setModulatorMix(0.2)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1.999)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\Shape(
            0.0, [
                [1.0, 1.00],
                [0.0, 12.0]
            ]
        )
    )
    ->setCarrierMix(0.25)
    ->setOutputLevel(0.075)
    ->setPitchLFODepth(0.06)
    ->setPitchLFORate(4.5)
    ->enablePitchLFO(true, true)
;


$oSequencer
    ->addMachine('chip', $oChipMachine)
    ->addMachine('drums', $oDrumMachine)
    ->addMachine('bass', $oBassLine)
    ->addMachine('pad', $oFMPad)
;
$oSequencer->allocatePattern('chip', [0, 2, 4, 6])
    ->addEvent(Audio\Sequence\Event::noteOn('C2', 50), 2, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C2', 50), 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('D#2', 50), 2, 16 + 12)

    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 0, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 1, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 60), 0, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 1, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 48), 0, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 40), 1, 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 0, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 1, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 40), 0, 14)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 1, 16 + 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 40), 0, 16 + 2)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 50), 1, 16 + 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 30), 0, 16 + 6)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 1, 16 + 8)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 48), 0, 16 + 9)
    ->addEvent(Audio\Sequence\Event::noteOn('G3', 44), 1, 16 + 11)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 40), 0, 16 + 13)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 38), 1, 16 + 14);

$oSequencer->allocatePattern('chip', [1, 3, 5, 7])
    ->addEvent(Audio\Sequence\Event::noteOn('F2', 50), 2, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('F1', 50), 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('D#2', 50), 2, 16 + 12)

    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 30), 0, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 32), 1, 1)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 34), 0, 2)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 36), 1, 3)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 38), 0, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 40), 1, 5)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 42), 0, 6)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 44), 1, 7)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 46), 0, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 48), 1, 9)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 50), 0, 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 52), 1, 11)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 55), 0, 12)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 60), 1, 13)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 70), 0, 14)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 1, 15)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 70), 0, 16 + 0)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 68), 0, 16 + 1)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 66), 1, 16 + 2)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 64), 0, 16 + 3)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 62), 1, 16 + 4)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 60), 0, 16 + 5)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 58), 1, 16 + 6)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 56), 0, 16 + 7)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 54), 1, 16 + 8)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 52), 0, 16 + 9)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 50), 1, 16 + 10)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 48), 0, 16 + 11)
    ->addEvent(Audio\Sequence\Event::noteOn('D#3', 46), 1, 16 + 12)
    ->addEvent(Audio\Sequence\Event::noteOn('F3', 44), 0, 16 + 13)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 40), 1, 16 + 14)
    ->addEvent(Audio\Sequence\Event::noteOn('A#3', 38), 0, 16 + 15)
;


$oSequencer->allocatePattern('drums', [2, 8, 9])
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 100), 0, 0, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('F4', 50), 4, 31, 0)
;

$oSequencer->allocatePattern('drums', [3])
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 100), 0, 0, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 60), 2, 1, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 50), 3, 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 30), 2, 3, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 50), 1, 29, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('E4', 50), 1, 31, 0)
;


$oSequencer->allocatePattern('drums', [4, 5, 6, 7])
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 100), 0, 0, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 60), 2, 1, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 50), 3, 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 30), 2, 3, 4)
    ->addEvent(Audio\Sequence\Event::noteOn('A3', 100), 1, 4, 8)
    ->addEvent(Audio\Sequence\Event::noteOn('A4', 70), 1, 15, 0)

;


$oSequencer->allocatePattern('bass', [4, 5, 6, 8])
    ->addEvent(Audio\Sequence\Event::noteOn('C2', 60), 0, 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOff(), 0, 4, 4)
;

$oSequencer->allocatePattern('bass', [7])
    ->addEvent(Audio\Sequence\Event::noteOn('F2', 50), 0, 2, 4)
    ->addEvent(Audio\Sequence\Event::noteOff(), 0, 4, 4)
;

$oSequencer->allocatePattern('pad', [6])
    ->addEvent(Audio\Sequence\Event::noteOn('C3', 50), 0, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('E3', 50), 1, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('G3', 50), 2, 0)
    ->addEvent(Audio\Sequence\Event::noteOn('C4', 50), 3, 0)
;


$oSequencer->allocatePattern('pad', [7])
    ->addEvent(Audio\Sequence\Event::setNote('D#3'), 1, 0)
    ->addEvent(Audio\Sequence\Event::setNote('A#3'), 3, 0)

;


$oOutput = Audio\Output\Piped::create();
$oOutput->open();

$oSequencer->playSequence(
    $oOutput,
    4.0
);

$oOutput->close();
