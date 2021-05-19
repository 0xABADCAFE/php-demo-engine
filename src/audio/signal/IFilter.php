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
interface IFilter extends IStream {

    const
        // Cutoff range is normalised
        MIN_CUTOFF    = 0.001,
        DEF_CUTOFF    = 0.5,
        MAX_CUTOFF    = 1.0,

        // Resonance range is normalised
        MIN_RESONANCE = 0.0,
        DEF_RESONANCE = 0.0,
        MAX_RESONANCE = 1.0
    ;

    /**
     * Set the baseline cutoff level. In the absence of a cutoff controller, this is the fixed cutoff. Otherwise it is
     * the cutoff value when the control signal level is 1.0. Values sould be in the range MIN_CUTOFF to MAX_CUTOFF.
     * Note that values above MAX_CUTOFF may be tolerated depending on the filter type.
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setCutoff(float $fCutoff) : self;

    /**
     * Set a control stream (envelope, LFO etc) for the cutoff control. Setting null clears any existing control.
     *
     * @param  Audio\Signal\IStream|null $oCutoffControl
     * @return self
     */
    public function setCutoffControl(?Audio\Signal\IStream $oCutoffControl) : self;

    /**
     * Set the baseline resonance level. In the absence of a resonance controller, this is the fixed resonance.
     * Otherwise it is the resonance value when the control signal level is 1.0. Values should be in the range
     * MIN_RESONANCE to MAX_RESONANCE. Note that values above MAX_RESONANCe may be tolerated depending on the filter
     * type.
     *
     * @param  float $fResonance
     * @return self
     */
    public function setResonance(float $fResonance) : self;

    /**
     * Set a control stream (envelope, LFO etc) for the resonance control. Setting null clears any existing control.
     *
     * @param  Audio\Signal\IStream|null $oResonanceControl
     * @return self
     */
    public function setResonanceControl(?Audio\Signal\IStream $oResonanceControl) : self;
}
