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
    'clave'    => Audio\Machine\Percussion\AnalogueClave::class
];

$aOptions = getopt('s::n::v::f');

$sSound    = $aOptions['s'] ?? 'kick';
if (!isset(SOUNDS[$sSound])) {
    echo "Unrecognised sound name '", $sSound, "'\n";
    exit(1);
}

$sNote     = $aOptions['n'] ?? 'A4';
if (!isset(Audio\Note::NOTE_NAMES[$sNote])) {
    echo "Error: Unrecognised note name '", $sNote, "'\n";
    exit(1);
}

$iVelocity = (int)($aOptions['v'] ?? 100);
$iVelocity = min(max($iVelocity, 1), 127);

$bWriteToFile = isset($aOptions['f']);

printf(
    "Playing %s at note %s, velocity %d...\n",
    $sSound,
    $sNote,
    $iVelocity
);

$sClass = SOUNDS[$sSound];

$oPCMOut = $bWriteToFile ?
    new Audio\Output\Wav(sprintf("output/%s-%s-%d.wav", $sSound, $sNote, $iVelocity)) :
    $oPCMOut = Audio\Output\Piped::create();


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

if (!$bWriteToFile) {
    $iTailPackets = 100;
    while ($iTailPackets--) {
        $oPCMOut->write($oOutput->emit());
    }
}
$oPCMOut->close();
