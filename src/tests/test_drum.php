<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

const SOUNDS = [
    'kick'     => Audio\Machine\Percussion\AnalogueKick::class,
    'snare'    => Audio\Machine\Percussion\AnalogueSnare::class,
    'hhclosed' => Audio\Machine\Percussion\AnalogueHHClosed::class,
    'hhopen'   => Audio\Machine\Percussion\AnalogueHHOpen::class,
    'clap'     => Audio\Machine\Percussion\AnalogueClap::class,
    'cowbell'  => Audio\Machine\Percussion\AnalogueCowbell::class,
    'tom'      => Audio\Machine\Percussion\AnalogueTom::class,
    'clave'    =>  Audio\Machine\Percussion\AnalogueClave::class
];

$sSound = strtolower($_SERVER['argv'][1] ?? 'kick');
if (!isset(SOUNDS[$sSound])) {
    echo "Unrecognised sound name '", $sSound, "'\n";
    exit();
}

$sNote = $_SERVER['argv'][2] ?? 'A4';
if (!isset(Audio\Note::NOTE_NAMES[$sNote])) {
    echo "Error: Unrecognised note name '", $sNote, "'\n";
    exit();
}

$iVelocity = min(max((int)($_SERVER['argv'][3] ?? 100), 1), 127);

printf(
    "Playing %s at note %s, velocity %d...\n",
    $sSound,
    $sNote,
    $iVelocity
);

$sClass = SOUNDS[$sSound];

// Open the audio
$oPCMOut = Audio\Output\Piped::create();
//$oPCMOut = new Audio\Output\Wav(sprintf("output/%s-%s-%d.wav", $sSound, $sNote, $iVelocity));
$oPCMOut->open();

$oDrum  = new $sClass;
$oOutput = $oDrum
    ->setNote($sNote)
    ->setVelocity($iVelocity)
    ->getOutputStream()
    ->reset()
    ->enable();

while ($oOutput->isEnabled()) {
    $oPCMOut->write($oOutput->emit());
}
$oPCMOut->close();
