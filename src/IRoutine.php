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

namespace ABadCafe\PDE;

/**
 * IRoutine
 *
 * Base interface for routines
 */
interface IRoutine extends IParameterisable {

    /**
     * Common parameters. These can be redefined by implementing classes.
     */
    const COMMON_PARAMETERS = [
        'fDuration' => 0.0, // How long the routine should run for after being enabled
    ];

    /**
     * Expected constructor profile
     *
     * @param IDisplay $oDisplay
     * @param mixed[]  $aParameters
     */
    public function __construct(IDisplay $oDisplay, array $aParameters = []);

    /**
     * Set a new display.
     *
     * @param  IDisplay $oDisplay
     * @return self     fluent
     */
    public function setDisplay(IDisplay $oDisplay) : self;

    /**
     * Render a frame to the given display
     *
     * @param  int   $iFrameNumber
     * @param  float $fTimeIndex
     * @return self  fluent
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self;

    /**
     * Enable the routine. This will be called during the event processing stage before
     * clearing the display and then rendering the next frame. Routines can hook into
     * this in order to do things like reset internal state, capture the last frame etc.
     *
     * @param  int   $iFrameNumber
     * @param  float $fTimeIndex
     * @return self  fluent
     */
    public function enable(int $iFrameNumber, float $fTimeIndex): self;

    /**
     * Disable the routine. This will be called during the event processing stage before
     * clearing the display and then rendering the next frame.
     *
     * @param  int   $iFrameNumber
     * @param  float $fTimeIndex
     * @return self  fluent
     */
    public function disable(int $iFrameNumber, float $fTimeIndex): self;
}
