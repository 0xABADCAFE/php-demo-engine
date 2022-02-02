<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

use function \preg_match, \sqrt, \pow;

require_once '../PDE.php';

const WAVETABLE = [
    Audio\Signal\IWaveform::SINE               => "Sine",
    Audio\Signal\IWaveform::SINE_HALF_RECT     => "Sine Half Rectified, normalised",
    Audio\Signal\IWaveform::SINE_FULL_RECT     => "Sine Full Rectified, normalised",
    Audio\Signal\IWaveform::SINE_SAW           => "Sine Saw",
    Audio\Signal\IWaveform::SINE_PINCH         => "Sine Pinch",
    Audio\Signal\IWaveform::SINE_CUT           => "Sine Cut",
    Audio\Signal\IWaveform::TRIANGLE           => "Triangle",
    Audio\Signal\IWaveform::TRIANGLE_HALF_RECT => "Triangle Half Rectified, normalised",
    Audio\Signal\IWaveform::SAW                => "Saw",
    Audio\Signal\IWaveform::SQUARE             => "Square",
    Audio\Signal\IWaveform::PULSE              => "Pulse, 25% duty",
    Audio\Signal\IWaveform::NOISE              => "White noise"
];

const MIN_FREQ = 20.0;
const MAX_FREQ = 12000.0;
const MIN_DB   = -96.0;

const PHON_40_CONTOUR = [

    // Hz   SPL dB
    [20,	99.85],
    [25,	93.94],
    [31.5,	88.17],
    [40,	82.63],
    [50,	77.78],
    [63,	73.08],
    [80,	68.48],
    [100,	64.37],
    [125,	60.59],
    [160,	56.70],
    [200,	53.41],
    [250,	50.40],
    [315,	47.58],
    [400,	44.98],
    [500,	43.05],
    [630,	41.34],
    [800,	40.06],

    [1000,	40.00], // Reference value
    [1250,	41.82],
    [1600,	42.51],
    [2000,	39.23],
    [2500,	36.51],
    [3150,	35.61],
    [4000,	36.65],
    [5000,	40.01],
    [6300,	45.83],
    [8000,	51.80],
    [10000,	54.28],
    [12500,	51.49],
];

function calculatePerceivedLoudness(float $fFrequency, float $fDecibel): float {
    if ($fFrequency < 20.0 || $fFrequency > 125000) {
        return $fDecibel;
    }

    // Find the first entry in the phon40 curve that's bigger than the input frequency.
    $i = 0;
    while ($fFrequency >= PHON_40_CONTOUR[$i][0]) {
        ++$i;
    }

    // Estimate the gradient between this entry and the one preceeding it.
    $fGradient =
        (PHON_40_CONTOUR[$i][1] - PHON_40_CONTOUR[$i-1][1]) /
        (PHON_40_CONTOUR[$i][0] - PHON_40_CONTOUR[$i-1][0]);


    // Interpolate the value
    $fInterpolated = PHON_40_CONTOUR[$i-1][1] + ($fFrequency - PHON_40_CONTOUR[$i-1][0]) * $fGradient;

    // Turn into an adjustment for the frequency
    $fInterpolated -= 40.0; // 40 is the reference value for the curve.

    // Subtract the adjustment. This will attenuate frequencies away from the phon40 minima and boost
    // those closest
    return $fDecibel - $fInterpolated;
}


printf(
    "Examining spectrum data between %.f Hz - %.f Hz for components above %.f dB\n",
    MIN_FREQ,
    MAX_FREQ,
    MIN_DB
);

foreach (WAVETABLE as $iEnum => $sName) {
    $fPower = 0.0;
    $aData = explode("\n", file_get_contents(sprintf('output/spectrum-%d.txt', $iEnum)));
    foreach ($aData as $sEntry) {
        if (preg_match('/^(\d+\.\d+)\s+(-{0,1}\d+\.\d+)$/', $sEntry, $aMatches)) {
            $fFrequency = (float)$aMatches[1];
            $fDecibel   = (float)$aMatches[2];

            if ($fFrequency < MIN_FREQ || $fDecibel < MIN_DB) {
                continue;
            } else if ($fFrequency > MAX_FREQ) {
                break;
            }

            $fDecibel = calculatePerceivedLoudness($fFrequency, $fDecibel);

            $fPower += sqrt(10.0 ** ($fDecibel * 0.1));
        }
    }
    printf("Waveform %2d [ %-35s ] Power %.f\n", $iEnum, $sName, $fPower);
}
