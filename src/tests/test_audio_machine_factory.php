<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iTests   = 0;
$iSuccess = 0;

echo "Testing Audio Machine Factory...\n";

const MACHINES = [
    '{"type":"multifm", "operators":[{}, {}]}',
    '{"type":"multifm", "operators":[{"waveform":1}, {"waveform":2, "modifier":1}]}',
    '{"type":"multifm", "operators":[{"waveform": {"type": "rectifier", "waveform": {"type": "sine"}, "minLevel": -0.5, "maxLevel": 0.5, "fold": true }}, {"waveform":2, "modifier":1}]}',


    '{"type":"multifm", "operators":[{"ratio":1.5}, {"semitones": 3}]}',
    '{"type":"multifm", "operators":[{"outputmix": 0.5}, {"outputmix": 0.5}]}',

    '{"type":"multifm", "operators":[{"levellfo": {"rate": 10.0 }}, {"pitchlfo": {"depth": 0.25}}]}',

    '{"type":"multifm", "operators":[{"levelenv": {"type": "decay", "halflife": 0.2, "initial": 0.75}}, {"pitchenv": {"type": "decay", "halflife": 0.2, "initial": 0.75, "target": 0.25}}]}',

    '{"type":"multifm", "operators":[{"modulators":[{"source": 1, "index": 0.75}]}, {}]}',
];


foreach (MACHINES as $sDefinition) {
    ++$iTests;
    try {
        echo "\nTest case: ", $sDefinition, "\n";

        $oProduct = Audio\Machine\Factory::get()->createFrom(json_decode($sDefinition));
        ++$iSuccess;
    } catch (\Throwable $oError) {
        echo "\tCaught ", get_class($oError), " testing ", $sDefinition, ", ", $oError->getMessage(), "\n";
        throw $oError;
    }
}



printf("\tTests %d, Successes %d\n", $iTests, $iSuccess);
