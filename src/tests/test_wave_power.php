<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$aWaves = Audio\Signal\Waveform\Flyweight::get()
    ->getWaveforms(
        array_keys(Audio\Signal\IWaveform::ROOT_SPECTRAL_POWER)
    );

foreach ($aWaves as $iEnum => $oWaveform) {
    $oStream = new Audio\Signal\Oscillator\Sound(
        $oWaveform,
        1000.0
    );

    $oPCMOut = new Audio\Output\Wav(sprintf('output/waveform-%d-1kHz.wav', $iEnum));
    $oPCMOut->open();
    for ($j = 0; $j < 1000; ++$j) {
        $oPCMOut->write($oStream->emit());
    }
    $oPCMOut->close();
}
