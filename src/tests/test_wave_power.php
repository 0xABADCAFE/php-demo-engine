<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$aWaves = Audio\Signal\Waveform\Flyweight::get()->getWaveforms([
    Audio\Signal\IWaveform::SINE,
    Audio\Signal\IWaveform::SINE_HALF_RECT,
    Audio\Signal\IWaveform::SINE_FULL_RECT,
    Audio\Signal\IWaveform::SINE_SAW,
    Audio\Signal\IWaveform::SINE_PINCH,
    Audio\Signal\IWaveform::TRIANGLE,
    Audio\Signal\IWaveform::TRIANGLE_HALF_RECT,
    Audio\Signal\IWaveform::SAW,
    Audio\Signal\IWaveform::SQUARE,
    Audio\Signal\IWaveform::PULSE,
    Audio\Signal\IWaveform::NOISE
]);

$oInput = new Audio\Signal\Packet;

foreach ($aWaves as $iEnum => $oWaveform) {

    $oPCMOut = new Audio\Output\Wav(sprintf('output/waveform-%d.wav', $iEnum));
    $oPCMOut->open();
    $fStep = $oWaveform->getPeriod() / Audio\IConfig::PACKET_SIZE;
    for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
        $oInput[$i] = $i * $fStep;
    }
    for ($j=0; $j<1000; ++$j) {
        $oPCMOut->write($oWaveform->map($oInput));
    }
    $oPCMOut->close();

}
