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
        DEF_BG_COLOUR = IANSIControl::BLACK,
        DEF_FG_COLOUR = IANSIControl::WHITE,
        DEF_LUMA_CHAR = ' .,-~:;=!*+|%$#@',
        DEF_MAX_LUMA  = 15
    ;

    /**
     * Set the default foreground ANSI colour to use.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setForegroundColour(int $iColour) : self;

    /**
     * Set the default background ANSI colour to use.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setBackgroundColour(int $iColour) : self;

    /**
     * Returns the span width of the text display which will typically be the display width plus however many bytes
     * are needed for the newline (one or two potentially). You must use this value when calculating offsets into
     * the character buffer and not getWidth()!
     *
     * @return int
     */
    public function getCharacterWidth() : int;

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
