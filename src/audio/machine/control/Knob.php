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

namespace ABadCafe\PDE\Audio\Machine\Control;

/**
 * Boris.
 */
class Knob extends Definition {

    const SCALE_UINT8_FIXED_POINT_MAX = 255.0 / 256.0;

    public float $fMinOutput, $fMaxOutput;

    /**
     * @param callable $cApplicator - callback defined by the machine exporting the control that applies the change.
     */
    public function __construct(
        int      $iControllerNumber,
        callable $cApplicator,
        int      $iInitial   = 0,
        float    $fMinOutput = 0.0,
        float    $fMaxOutput = 1.0
    ) {
        $this->iControllerNumber = $iControllerNumber;
        $this->cApplicator       = $cApplicator;
        $this->iInitial          = $iInitial;
        $this->fMinOutput        = $fMinOutput;
        $this->fMaxOutput        = $fMaxOutput;
    }
}
