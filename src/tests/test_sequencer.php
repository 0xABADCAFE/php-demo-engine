<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oFMMarimba = new Audio\Machine\TwoOpFM(4);

$oFMMarimba
    ->setModulatorWaveform(Audio\Signal\IWaveform::TRIANGLE)
    ->setModulatorRatio(7.01)
    ->setModulatorLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.015)
    )
    ->setModulationIndex(0.2)
    ->setModulatorMix(0.15)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE)
    ->setCarrierRatio(1.99)
    ->setCarrierLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.1)
    )
    ->setOutputLevel(0.35)
;

$oSequencer = new Audio\Machine\Sequencer();

$oSequencer->addMachine('marimba', $oFMMarimba);

$oPattern = $oSequencer->createPattern('marimba', 1, [0, 2, 4, 6]);
$oPattern = $oSequencer->createPattern('marimba', 1, [1, 3, 5, 7]);

$aSequence = $oSequencer->getSequence('marimba');
ksort($aSequence);
print_r($aSequence);
