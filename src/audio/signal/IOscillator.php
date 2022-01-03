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
     * Set the waveform to use. Passing null disables the oscillator (emits silence).
     *
     * Implementations may clone the waveform instance passed to them so do not rely on getWaveform() returning
     * the same instance.
     *
     * @param  IWaveform|null $oWaveform
     * @return self
     */
    public function setWaveform(?IWaveform $oWaveform): self;

    public function getWaveform(): ?IWaveform;

    /**
     * Set the baseline frequency to emit.
     *
     * @param  float $fFrequency
     * @return self
     */
    public function setFrequency(float $fFrequency): self;
}
