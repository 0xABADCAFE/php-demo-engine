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

namespace ABadCafe\PDE\Audio;

/**
 * IControlCurve
 */
interface IControlCurve {

    const
        DEF_RANGE_MIN = 0.0,
        DEF_RANGE_MAX = 127.0,
        DEF_RANGE     = self::DEF_RANGE_MAX - self::DEF_RANGE_MIN,
        DEF_SCALE     = 1.0 / self::DEF_RANGE
    ;

    /**
     * Map a control value to some other value. The input value is floating point to allow for arbitrary
     * precision intervals but the implementation may quantize this if it's using a lookup, etc.
     *
     * @param  float $fControlValue
     * @return float
     */
    public function map(float $fControlValue) : float;
}
