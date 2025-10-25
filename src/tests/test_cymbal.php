<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: TR808 style cymbal synthesis test\n";

const OSC_FREQ = [
    800.0,
    540.0,
    522.7,
    396.6,
    304.4,
    205.3
];

$oOscBank = new Audio\Signal\Operator\FixedMixer();

$fAttenuate = 1.0/count(OSC_FREQ);

foreach (OSC_FREQ as $iId => $fFreq) {

    $oOscillator = new Audio\Signal\Oscillator\Sound(
        new Audio\Signal\Waveform\Square(),
        $fFreq
    );
    $oOscillator->setAntialiasMode(Audio\Signal\Oscillator\Sound::ANTIALIAS_OFF);
    $oOscBank->addInputStream(
        'osc_' . $iId,
        $oOscillator,
        $fAttenuate
    );
}

$oFilter1 = new Audio\Signal\Filter\BandPass(
    $oOscBank,
    0.5,
    0.5
);

$oFilter2 = new Audio\Signal\Filter\HighPass(
    $oOscBank,
    0.99,
    1.1
);

$oMixer = new Audio\Signal\Operator\FixedMixer();
$oMixer->addInputStream('f1', $oFilter1, 0.5);
$oMixer->addInputStream('f2', $oFilter2, 0.5);

$oEnvelope1 = new Audio\Signal\Envelope\DecayPulse(1.0, 0.2);

$oVCA1 = new Audio\Signal\Operator\Modulator($oMixer, $oEnvelope1);




// $oFilter1 = new Audio\Signal\Filter\BandPass(
//     $oOsc2,
//     1.0,
//     0.1,
//     new Audio\Signal\Envelope\DecayPulse(1.0, 0.02, 0.25)
// );
//
// $oFilter2 = new Audio\Signal\Filter\LowPass(
//     $oOsc2,
//     1.0,
//     0.0,
//     new Audio\Signal\Envelope\Shape(
//         1.0,
//         [
//             [0.0, 0.2],
//             [1.0, 1]
//         ]
//     )
// );



//$oPCMOut  = Audio\Output\Piped::create();
$oPCMOut = new Audio\Output\Wav('output/cymbal.wav');
$oPCMOut->open();

$iPackets = 500;
while($iPackets--) {
    $oPCMOut->write($oOscBank->emit());
}

$oPCMOut->close();
