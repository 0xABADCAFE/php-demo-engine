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

/**
 * SineSawHard
 *
 * Hard saw implementation of IWaveform based on the first falling edge of the sine wave.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class SineSawHard extends SineXForm {

    /**
     * Waveform period (interval after which it repeats).
     */
    const PERIOD = 1.0;

    const TRANSFORM = [
        // Quadrant phase shift, Bias Adjust, Scale.
        // This default configuration rearranges a sine wave into something resembling a soft saw wave.
        [ 1.0,  -1.0, 2.0],
        [ 0.0,  -1.0, 2.0],
        [-1.0,  -1.0, 2.0],
        [-2.0,  -1.0, 2.0]
    ];
}
