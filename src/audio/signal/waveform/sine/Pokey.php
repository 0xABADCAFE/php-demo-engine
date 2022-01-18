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
 * Pokey
 *
 * Fake Pokey implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Pokey extends SineXForm {

    const PERIOD = 2.0;

    const TRANSFORM = [
        // Quadrant phase shift, Bias Adjust, Scale.
        // This default configuration rearranges a sine wave into something resembling a soft saw wave.
        [ 2.0,  1.0, 0.25],
        [ 1.0,  -1.0, -0.25],
        [ 0.0,  1.0, 0.25],
        [ -1.0, -1.0, -0.25]
    ];
}
