<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oFMTest = new Audio\Machine\TwoOpFM(1);
$oFMTest
    ->setModulatorWaveform(Audio\Signal\IWaveform::SINE)
    ->setModulationIndex(0.0)
    ->setModulatorMix(0.0)
    ->setCarrierWaveform(Audio\Signal\IWaveform::SINE, Audio\Signal\Waveform\Rectifier::NONE)
    ->setCarrierRatio(1.0)
    ->setCarrierMix(1.0)
    ->setOutputLevel(1.0);
;

$oFMTest
    ->setVoiceNote(0, 'A4')
    ->startVoice(0);

$aModifiers = [
    Audio\Signal\Waveform\Rectifier::NONE,
    Audio\Signal\Waveform\Rectifier::HALF_RECT_P,
    Audio\Signal\Waveform\Rectifier::HALF_RECT_N,
    Audio\Signal\Waveform\Rectifier::HALF_RECT_P_FS,
    Audio\Signal\Waveform\Rectifier::HALF_RECT_N_FS,
    Audio\Signal\Waveform\Rectifier::FULL_RECT_P,
    Audio\Signal\Waveform\Rectifier::FULL_RECT_N,
    Audio\Signal\Waveform\Rectifier::FULL_RECT_P_FS,
    Audio\Signal\Waveform\Rectifier::FULL_RECT_N_FS,
];


// Open the audio
//$oPCMOut = Audio\Output\Piped::create();
$oPCMOut = new Audio\Output\Wav('test_fm.wav');
$oPCMOut->open();

foreach ($aModifiers as $iModifier) {
    $oFMTest->setCarrierWaveform(Audio\Signal\IWaveform::SINE, $iModifier);
    for ($i = 0; $i < 200; ++$i) {
        $oPCMOut->write($oFMTest->emit());
    }

}

$oPCMOut->close();
