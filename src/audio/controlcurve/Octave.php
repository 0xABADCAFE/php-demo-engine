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

namespace ABadCafe\PDE\Audio\ControlCurve;

use ABadCafe\PDE\Audio;
use \SPLFixedArray;

/**
 * Octave
 *
 * Output values double at a fixed interval. The standard configuration assumes the input value is a note number but
 * can be configured to any use case.
 */
class Octave implements Audio\IControlCurve {

    private float $fCentreOutput, $fScalePerOctave, $fCentrePosition;

    /**
     * Constructor
     *
     * @param $fCentreOutput   - Value at the centre reference
     * @param $fScalePerOctave - Scaling factor: 1.0 results in a doubling per octave, 0.5 a doubling every 2 octaves, etc.
     *                           Negative values invert the direction, i.e. -1.0 results in a halving per octave.
     * @param $fStepsPerOctave - Number of whole number input steps in an octave.
     * @param $fCentrePosition - Input value at which the output is $fCentreOutput
     */
    public function __construct(
        float $fCentreOutput,
        float $fScalePerOctave = 1.0,
        float $fStepsPerOctave = Audio\Note::SEMIS_PER_OCTAVE,
        float $fCentrePosition = Audio\Note::CENTRE_REFERENCE
    ) {
        $this->fCentreOutput      = $fCentreOutput;
        $fScalePerOctave         /= $fStepsPerOctave;
        $this->fScalePerOctave    = ($fScalePerOctave > 0.0 ?: -1.0/$fScalePerOctave);
        $this->fCentrePosition    = $fCentrePosition;
    }

    /**
     * @inheritDoc
     */
    public function map(float $fControlValue) : float {
        $fControlValue -= $this->fCentrePosition;
        return $this->fCentreOutput * (2.0 ** ($this->fScalePerOctave * $fControlValue));
    }
}

