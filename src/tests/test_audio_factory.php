<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iTests   = 0;
$iSuccess = 0;

echo "Testing ControlCurve Factory...\n";
const CONTROLCURVES = [
    '{"sType": "Flat", "fFixed": 0.25}',
    '{"sType": "Linear" }',
    '{"sType": "Linear", "fMinOutput": 0.25 }',
    '{"sType": "Linear", "fMinOutput": 0.25, "fMaxOutput": 0.75 }',
    '{"sType": "Linear", "fMinOutput": 0.25, "fMaxOutput": 0.75, "fMinInput": 16.0, "fMaxInput": 32.0 }',
    '{"sType": "Gamma" }',
    '{"sType": "Gamma", "fGamma": 1.00001 }',
    '{"sType": "Gamma", "fGamma": 0.5 }',
    '{"sType": "Gamma", "fGamma": 2.0 }',
];

foreach (CONTROLCURVES as $sDefinition) {
    ++$iTests;
    try {
        $oProduct = Audio\ControlCurve\Factory::get()->createFrom(json_decode($sDefinition));
        ++$iSuccess;
    } catch (\Throwable $oError) {
        echo "\tCaught ", get_class($oError), " testing ", $sDefinition, ", ", $oError->getMessage(), "\n";
    }
}

printf("\tTests %d, Successes %d\n", $iTests, $iSuccess);


echo "Testing Waveform Factory...\n";
const WAVEFORMS = [
    '{"sType": "Sine"}',
    '{"sType": "Triangle"}',
    '{"sType": "Saw"}',
    '{"sType": "Square"}',
    '{"sType": "Pulse"}',
    '{"sType": "Noise"}',
    '{"sType": "Saw", "bAliased": false}',
    '{"sType": "Square", "bAliased": false}',
    '{"sType": "Pulse",  "bAliased": false}',
    '{"sType": "Saw", "bAliased": true}',
    '{"sType": "Square", "bAliased": true}',
    '{"sType": "Pulse",  "bAliased": true}',
    '{"sType": "Rectifier", "Waveform": {"sType": "Sine"} }',
    '{"sType": "Rectifier", "Waveform": {"sType": "Sine"}, "iPreset": 2 }',
    '{"sType": "Rectifier", "Waveform": {"sType": "Sine"}, "fMinLevel": -0.5, "fMaxLevel": 0.5, "bFold": true }'
];

foreach (WAVEFORMS as $sDefinition) {
    ++$iTests;
    try {
        $oProduct = Audio\Signal\Waveform\Factory::get()->createFrom(json_decode($sDefinition));
        ++$iSuccess;
    } catch (\Throwable $oError) {
        echo "\tCaught ", get_class($oError), " testing ", $sDefinition, ", ", $oError->getMessage(), "\n";
    }
}

printf("\tTests %d, Successes %d\n", $iTests, $iSuccess);

echo "Testing Oscillator Factory...\n";
const OSCILLATORS = [
    '{"sType": "LFO", "Waveform":%s}',
    '{"sType": "LFOOneToZero", "Waveform":%s}',
    '{"sType": "LFOZeroToOne", "Waveform":%s}',
    '{"sType": "Audio", "Waveform":%s}'
];

foreach (OSCILLATORS as $sDefinition) {
    try {
        $oDefinition = json_decode(sprintf($sDefinition, '0'));
        unset($oDefinition->waveform);
        ++$iTests;
        $oProduct = Audio\Signal\Oscillator\Factory::get()->createFrom($oDefinition);
        ++$iSuccess;
        foreach (WAVEFORMS as $sWaveform) {
            ++$iTests;
            $sDefinitionFull = sprintf($sDefinition, $sWaveform);
            try {
                $oProduct = Audio\Signal\Oscillator\Factory::get()->createFrom(json_decode($sDefinitionFull));
                ++$iSuccess;
            } catch (\Throwable $oError) {
                echo "\tCaught ", get_class($oError), " testing ", $sDefinitionFull, ", ", $oError->getMessage(), "\n";
            }
        }

    } catch (\Throwable $oError) {
        echo "\tCaught ", get_class($oError), " testing ", $sDefinition, ", ", $oError->getMessage(), "\n";
    }
}

printf("\tTests %d, Successes %d\n", $iTests, $iSuccess);

echo "Testing Envelope Factory...\n";

const ENVELOPES = [
    '{"sType": "Decay"}',
    '{"sType": "Decay", "fInitial": 0.5}',
    '{"sType": "Decay", "fHalfLife": 0.1}',
    '{"sType": "Decay", "fHalfLife": 0.2, "fInitial": 0.75}',
    '{"sType": "Decay", "fHalfLife": 0.3, "fTarget": 0.5}',
    '{"sType": "Decay", "fHalfLife": 1.5, "fInitial": 12, "fTarget": 8}',
    '{"sType": "Shape", "aPoints": [[1.0, 5.0]]}',
    '{"sType": "Shape", "fInitial": 0.5, "aPoints": [[1.0, 5.0], [0.0, 10.0]]}',
];

foreach (ENVELOPES as $sDefinition) {
    ++$iTests;
    try {
        $oProduct = Audio\Signal\Envelope\Factory::get()->createFrom(json_decode($sDefinition));
        ++$iSuccess;
    } catch (\Throwable $oError) {
        echo "\tCaught ", get_class($oError), " testing ", $sDefinition, ", ", $oError->getMessage(), "\n";
    }
}

printf("\tTests %d, Successes %d\n", $iTests, $iSuccess);
