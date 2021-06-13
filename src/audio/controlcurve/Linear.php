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
 * Linear
 *
 * Linerarly interpolates the input value between the defined output range
 */
class Linear implements Audio\IControlCurve {

    private float $fOutBase, $fOutRange, $fInBase, $fInScale;

    public function __construct(
        float $fOutMinValue,
        float $fOutMaxValue,
        float $fInRangeMin = self::DEF_RANGE_MIN,
        float $fInRangeMax = self::DEF_RANGE_MAX
    ) {
        $this->fOutBase     = $fOutMinValue;
        $this->fOutRange    = $fOutMaxValue - $fOutMinValue;
        $this->fInBase      = $fInRangeMin;
        $this->fInScale     = 1.0 / ($fInRangeMax - $fInRangeMin);
    }

    /**
     * @inheritDoc
     */
    public function map(float $fControlValue) : float {
        $fControlValue = $this->fInScale * ($fControlValue - $this->fInBase);
        return $this->fOutBase + $fControlValue * $this->fOutRange;
    }
}
