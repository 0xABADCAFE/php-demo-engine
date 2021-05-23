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
 * Interface for machines (synthesis units).
 */
interface IMachine extends Signal\IStream {

    const
        MIN_POLYPHONY = 1,
        MAX_POLYPHONY = 8
    ;

    /**
     * Start a note on the specified channel. Does nothing if the channel is out of range.
     *
     * @param  string $sNoteName
     * @param  int    $iVelocity
     * @param  int    $iChannel
     * @return self
     */
    public function noteOn(string $sNoteName, int $iVelocity, int $iChannel) : self;

    /**
     * Stops a note on the specified channel. Does nothing if the channel is out of range.
     *
     * @param  int    $iChannel
     * @return self
     */
    public function noteOff(int $iChannel) : self;
}

