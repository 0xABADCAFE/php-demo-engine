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
        // Basic waveforms
        SINE               = 0,
        TRIANGLE           = 1,
        SAW                = 2,
        SQUARE             = 3,
        NOISE              = 4,

        // Waveform variants
        SINE_HALF_RECT     = 5, // Half rectified sine wave, adjusted to -1.0 ... 1.0 range
        SINE_FULL_RECT     = 6  // Fully rectified sine wave, adjusted to -1.0 ... 1.0 range
    ;

    /**
     * Returns the period of this function, i.e. the numeric interval after which it's output cycles.
     *
     * @return float
     */
    public function getPeriod() : float;

    /**
     * Calculate a Packets worth of output values for a Packets worth of input values
     *
     * @param Packet $oInput
     * @return Packet
     *
     */
    public function map(Packet $oInput) : Packet;
}
