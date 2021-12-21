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
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
interface IEnvelope extends IStream {

    const MIN_TIME_SCALE = 0.01;

    /**
     * Set a scaling factor for envelope timing. A smaller value results in a faster envelope. Use to simlulate the
     * effects of higher notes decaying faster, etc. This should be set whenever we start a new note.
     *
     * @param  float $fTimeScale
     * @return self
     */
    public function setTimeScale(float $fTimeScale): self;

    /**
     * Set a scaling factor for envelope levels. A smaller value results in a quieter envelope. Use to simlulate the
     * effects of higher notes having lower overall energy, or higher velocities having greater, etc. This should be
     * set whenever we start a new note.
     *
     * @param  float $fTimeScale
     * @return self
     */
    public function setLevelScale(float $fLevelScale): self;
}
