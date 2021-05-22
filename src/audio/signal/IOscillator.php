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
interface IOscillator extends IStream {

    /**
     * Enable the oscillator.
     *
     * @return self
     */
    public function enable() : self;

    /**
     * Disable the oscillator: emit() Will return silence packets until enabled.
     *
     * @return self
     */
    public function disable() : self;

    /**
     * Set the waveform to use. Passing null disables the oscillator (emits silence).
     *
     * @param  IWaveform|null $oWaveform
     * @return self
     */
    public function setWaveform(?IWaveform $oWaveform) : self;

    /**
     * Set the baseline frequency to emit.
     *
     * @param  float $fFrequency
     * @return self
     */
    public function setFrequency(float $fFrequency) : self;
}
