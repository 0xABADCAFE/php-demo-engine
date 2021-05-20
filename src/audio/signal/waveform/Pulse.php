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

    private float $fThreshold = 0.25;

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
        foreach ($oInput as $i => $fTime) {
            $oOutput[$i] = ((ceil($fTime) - $fTime) > $this->fThreshold) ? 1.0 : -1.0;
        }
        return $oOutput;
    }
}
