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
 * SineSaw
 *
 * Sinrwave implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class SineSaw extends SineXForm {

    const TRANSFORM = [
        // Quadrant phase shift, Bias Adjust, Scale.
        // This default configuration rearranges a sine wave into something resembling a soft saw wave.
        [ 3.0,  1.0, 1.0],
        [ 0.0,  0.0, 1.0],
        [ 0.0,  0.0, 1.0],
        [-3.0,  -1.0, 1.0]
    ];
}
