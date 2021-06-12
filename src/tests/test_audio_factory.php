<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iTests   = 0;
$iSuccess = 0;

echo "Testing Waveform Factory...\n";
const WAVEFORMS = [
    '{"type": "sine"}',
    '{"type": "triangle"}',
    '{"type": "saw"}',
    '{"type": "square"}',
    '{"type": "pulse"}',
    '{"type": "noise"}',
    '{"type": "saw", "aliased": false}',
    '{"type": "square", "aliased": false}',
    '{"type": "pulse",  "aliased": false}',
    '{"type": "saw", "aliased": true}',
    '{"type": "square", "aliased": true}',
    '{"type": "pulse",  "aliased": true}',
    '{"type": "rectifier", "waveform": {"type": "sine"} }',
    '{"type": "rectifier", "waveform": {"type": "sine"}, "preset": 2 }',
    '{"type": "rectifier", "waveform": {"type": "sine"}, "minLevel": -0.5, "maxLevel": 0.5, "fold": true }'
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
    '{"type": "lfo", "waveform":%s}',
    '{"type": "lfo1to0", "waveform":%s}',
    '{"type": "lfo0to1", "waveform":%s}',
    '{"type": "audio", "waveform":%s}'
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
    '{"type": "decay"}',
    '{"type": "decay", "initial": 0.5}',
    '{"type": "decay", "halflife": 0.1}',
    '{"type": "decay", "halflife": 0.2, "initial": 0.75}',
    '{"type": "decay", "halflife": 0.3, "target": 0.5}',
    '{"type": "decay", "halflife": 1.5, "initial": 12, "target": 8}',
    '{"type": "shape", "points": [[1.0, 5.0]]}',
    '{"type": "shape", "initial": 0.5, "points": [[1.0, 5.0], [0.0, 10.0]]}',
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
