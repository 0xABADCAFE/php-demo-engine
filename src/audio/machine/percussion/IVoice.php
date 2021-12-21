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

namespace ABadCafe\PDE\Audio\Machine\Percussion;
use ABadCafe\PDE\Audio;

/**
 * IVoice
 *
 * Common interface for percussive voice generators. These contain black box circuit implementations that have their
 * own interpretations of note number and velocity.
 */
interface IVoice {

    /**
     * Set the note name. This can be interpreted in various ways by the implementor, it doesn't just have to be about
     * pitch.
     *
     * @param  string $sNote : @see Audio\Note
     * @return self
     */
    public function setNote(string $sNote): self;

    /**
     * Set the velocity. This can be interpreted in various ways by the implementor, it doesn't just have to be about
     * volume. Velocity range is nominally in the range 0 - 127.
     *
     * @param  int $iVelocity
     * @return self
     */
    public function setVelocity(int $iVelocity): self;

    /**
     * Get the output stream.
     *
     * @return Audio\Signal\IStream
     */
    public function getOutputStream(): Audio\Signal\IStream;
}
