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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

/**
 * IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
interface IWaveform {

    const
        // Basic waveform enumerations
        SINE               = 0,
        SINE_HALF_RECT     = 1,
        SINE_FULL_RECT     = 2,
        SINE_SAW           = 3,
        SINE_PINCH         = 4,
        TRIANGLE           = 10,
        TRIANGLE_HALF_RECT = 11,
        SAW                = 20,
        SAW_HALF_RECT      = 21,
        SAW_ALIASED        = 22,
        SQUARE             = 30,
        SQUARE_ALIASED     = 31,
        PULSE              = 40,
        PULSE_ALIASED      = 41,
        NOISE              = 50
    ;

    /**
     * Returns the period of this function, i.e. the numeric interval after which it's output cycles.
     *
     * @return float
     */
    public function getPeriod(): float;

    /**
     * Calculate a Packets worth of output values for a Packets worth of input values
     *
     * @param  Packet $oInput
     * @return Packet
     *
     */
    public function map(Packet $oInput): Packet;
}
