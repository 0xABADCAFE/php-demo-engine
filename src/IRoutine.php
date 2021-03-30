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
 *                             P(?:ointless|ortable|HP) Demo Engine/
 */

declare(strict_types=1);

namespace ABadCafe\PDE;

/**
 * IRoutine
 *
 * Base interface for routines
 */
interface IRoutine {

    /**
     * Get the priority of the routine. Routines with higher priority will draw over
     * those of lower priority. This is intended to allow multiple effects to be on
     * the display at one time.
     *
     * @return int
     */
    public function getPriotity() : int;

    /**
     * Render a frame to the given display
     *
     * @param  IDisplay $oDisplay
     * @param  int      $iFrameNumber
     * @param  float    $fTimeIndex
     * @return self     fluent
     */
    public function render(IDisplay $oDisplay, int $iFrameNumber, float $fTimeIndex) : self;

    /**
     * Accepts a key/value set of parameters to change. This can be a sequence event in
     * the overall timeline and will generally take effect on the next call to render().
     *
     * The implementation should not throw here. rather:
     *     Unknown parameter names will be ignored.
     *     The type of a parameter will be force cast (where appropriate)
     *     Out of range values will be clamped..
     *
     * @param  array $aParams [key => value]
     * @return self  fluent
     */
    public function setParameters(array $aParams) : self;
}
