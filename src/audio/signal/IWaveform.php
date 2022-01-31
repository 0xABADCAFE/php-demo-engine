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
use ABadCafe\PDE\Util;

/**
 * IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
interface IWaveform extends Util\ISometimesShareable {

    const
        // Basic waveform enumerations
        SINE               = 0,
        SINE_HALF_RECT     = 1,
        SINE_FULL_RECT     = 2,
        SINE_SAW           = 3,
        SINE_PINCH         = 4,
        SINE_CUT           = 5,
        SINE_SAW_HARD      = 6,
        TRIANGLE           = 10,
        TRIANGLE_HALF_RECT = 11,
        SAW                = 20,
        SAW_HALF_RECT      = 21,
        SQUARE             = 30,
        POKEY              = 32,
        PULSE              = 40,
        NOISE              = 50
    ;

    /**
     * Determined from the spectrum plot of each wave at 1kHz fundamental, adjusted for Phon 40 curve and
     * converted back to root power per frequency and integrated. Used to estimate an attenuation relative to
     * a sine wave to obtain an approximately equal loudness. This is a very basic approximation.
     */

    const SINE_SPECTRAL_POWER = 2.104447;
    const ROOT_SPECTRAL_POWER = [
        self::SINE               => 1.0,
        self::SINE_HALF_RECT     => self::SINE_SPECTRAL_POWER / 3.421964,
        self::SINE_FULL_RECT     => self::SINE_SPECTRAL_POWER / 2.635461,
        self::SINE_SAW           => self::SINE_SPECTRAL_POWER / 2.632815,
        self::SINE_PINCH         => self::SINE_SPECTRAL_POWER / 2.169089,
        self::SINE_CUT           => self::SINE_SPECTRAL_POWER / 2.804219,
        self::SINE_SAW_HARD      => self::SINE_SPECTRAL_POWER / 3.485038, // guess
        self::TRIANGLE           => self::SINE_SPECTRAL_POWER / 2.097255,
        self::TRIANGLE_HALF_RECT => self::SINE_SPECTRAL_POWER / 3.099708,
        self::SAW                => self::SINE_SPECTRAL_POWER / 3.485038,
        self::SQUARE             => self::SINE_SPECTRAL_POWER / 4.466541,
        self::POKEY              => self::SINE_SPECTRAL_POWER / 4.366541, // guess
        self::PULSE              => self::SINE_SPECTRAL_POWER / 4.952629,
        self::NOISE              => 0.5
    ];

    public function share(): self;

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

    /**
     * Calculate the output for a single input.
     */
    public function value(float $fInput): float;
}
