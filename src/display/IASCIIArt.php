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

namespace ABadCafe\PDE\Display;

/**
 * IASCIIArt
 *
 * Interface for displays that render using ASCII art techniques.
 */
interface IASCIIArt {

    const
        DEF_LUMA_CHAR = ' .,-~:;=!*+|%$#@',
        DEF_MAX_LUMA  = 15
    ;

    /**
     * Get the raw display buffer, aka 1337 mode, lol. String is returned by refrence so that modifying it has the
     * desired effect.
     *
     * @return string&
     */
    public function &getCharacterBuffer() : string;

    /**
     * Return an indexable string of characters that can be used to simulate luminance.
     *
     * @return string
     */
    public function getLuminanceCharacters() : string;

    /**
     * Return the largest luminance, i.e. the last index in the luminance character set.
     *
     * @return int
     */
    public function getMaxLuminance() : int;

    /**
     * Install a new luminance character set. Must be at least 2 characters.
     *
     * @param  string $sCharacters
     * @return self   fluent
     * @throws \LengthException
     */
    public function setLuminanceCharacters(string $sCharacters) : self;
}
