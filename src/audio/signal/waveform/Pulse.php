<?php
/**
 *                   ______                            __
 *           __     /\\\\\\\\_                        /\\\
 *          /\\\  /\\\//////\\\_                      \/\\\
 *        /\\\//  \///     \//\\\    ________       ___\/\\\         _______
 *      /\\\//               /\\\   /\\\\\\\\\_    /\\\\\\\\\       /\\\\\\\\_
 *    /\\\//_              /\\\\/   /\\\/////\\\   /\\\////\\\     /\\\/////\\\
 *    \////\\\ __          /\\\/    \/\\\   \/\\\  \/\\\  \/\\\    /\\\\\\\\\\\
 *        \////\\\ __      \///_     \/\\\___\/\\\  \/\\\__\/\\\   \//\\\//////_
 *            \////\\\       /\\\     \/\\\\\\\\\\   \//\\\\\\\\\    \//\\\\\\\\\
 *                \///       \///      \/\\\//////     \/////////      \/////////
 *                                      \/\\\
 *                                       \///
 *
 *                         /P(?:ointless|ortable|HP) Demo Engine/
 */

declare(strict_types=1);

namespace ABadCafe\PDE\Audio\Signal\Waveform;
use ABadCafe\PDE\Audio\Signal;

/**
 * Pulse
 *
 * PWM implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Pulse implements Signal\IWaveform {

    const F_PERIOD = 1.0;

    private float
        $fPrev1 = 0.0,
        $fPrev2 = 0.0,
        $fPrev3 = 0.0,
        $fPrev4 = 0.0,
        $fThreshold = 0.25
    ;


    public function __construct(float $fThreshold = 0.25) {
        $this->fThreshold = $fThreshold;
    }

    /**
     * @inheritDoc
     */
    public function getPeriod() : float {
        return self::F_PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Signal\Packet $oInput) : Signal\Packet {
        $oOutput = clone $oInput;

        // Avoid sharp transitions at the edges with a simple hamming filter.
        $fPrev1  = $this->fPrev1;
        $fPrev2  = $this->fPrev2;
        $fPrev3  = $this->fPrev3;
        $fPrev4  = $this->fPrev4;

        foreach ($oInput as $i => $fTime) {
            $fSample = ((ceil($fTime) - $fTime) > $this->fThreshold) ? 1.0 : -1.0;
            $oOutput[$i] = 0.1 * (
                $fSample + $fPrev4 +
                2.0 * ($fPrev1 + $fPrev3)
                + 4.0 * $fPrev2
            );
            $fPrev4 = $fPrev3;
            $fPrev3 = $fPrev2;
            $fPrev2 = $fPrev1;
            $fPrev1 = $fSample;
        }

        $this->fPrev1 = $fPrev1;
        $this->fPrev2 = $fPrev2;
        $this->fPrev3 = $fPrev3;
        $this->fPrev4 = $fPrev4;

        return $oOutput;
    }
}