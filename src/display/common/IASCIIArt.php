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
        // ITU T.416 mapped colours using the 6x6x6 colour cube as a subsitute for the
        // "standard" ANSI 16 colours, which basically aren't standard.
        BLACK          = 16,  // 0:0:0
        DARK_GREY      = 102, // 2:2:2 // AKA Bright Black (lol)
        BRIGHT_GREY    = 188, // 4:4:4 // AKA White
        WHITE          = 231, // 5:5:5 // AKA Bright White
        RED            = 160, // 4:0:0
        BRIGHT_RED     = 196, // 5:0:0
        GREEN          = 40,  // 0:4:0
        BRIGHT_GREEN   = 46,  // 0:5:0
        YELLOW         = 184, // 4:4:0
        BRIGHT_YELLOW  = 226, // 5:5:0
        BLUE           = 20,  // 0:0:4
        BRIGHT_BLUE    = 21,  // 0:0:5
        MAGENTA        = 164, // 4:0:4
        BRIGHT_MAGENTA = 201, // 5:0:5
        CYAN           = 44,  // 0:4:4
        BRIGHT_CYAN    = 51,  // 0:5:5
        DEF_BG_COLOUR  = self::BLACK,
        DEF_FG_COLOUR  = self::WHITE,
        DEF_LUMA_CHAR  = ' .,-~:;=!*+|%$#@',
        DEF_MAX_LUMA   = 15
    ;

    /**
     * Remaps the low 16 values of the ITU T.416 specification to our hand selected values
     * to eliminate variance between terminals/platforms/etc.
     */
    const REMAP_DEFAULTS = [
        0  => self::BLACK,
        1  => self::RED,
        2  => self::GREEN,
        3  => self::YELLOW,
        4  => self::BLUE,
        5  => self::MAGENTA,
        6  => self::CYAN,
        7  => self::BRIGHT_GREY,
        8  => self::DARK_GREY,
        9  => self::BRIGHT_RED,
        10 => self::BRIGHT_GREEN,
        11 => self::BRIGHT_YELLOW,
        12 => self::BRIGHT_BLUE,
        13 => self::BRIGHT_MAGENTA,
        14 => self::BRIGHT_CYAN,
        15 => self::WHITE
    ];

    /**
     * Set the default foreground ANSI colour to use. Accepts a value in the range 0-255 which is set using the
     * ITU T.416 256 colour mode. If a value in the range 0-15 is passed, it will be remapped internally.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setForegroundColour(int $iColour) : self;

    /**
     * Set the default background ANSI colour to use. Accepts a value in the range 0-255 which is set using the
     * ITU T.416 256 colour mode.
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
     * Render a string of text starting at a given x/y coordinate. An optional max X and Y can be passed
     * to define a bounding box, otherwise the edges of the display are used. Text that exceeds the width will
     * be clipped.
     *
     * @param  string $sText
     * @param  int    $iX
     * @param  int    $iY
     * @param  int    $iMaxX - If < 1, the maximum X ordinate of the display is used
     * @param  int    $iMaxY - If < 1, the maximum Y ordinate of the display is used
     * @return self
     */
    public function writeTextBounded(string $sText, int $iX, int $iY, int $iMaxX = 0, $iMaxY = 0) : self;

    /**
     * Simple text render. Does not support new lines, all text after a new line is discarded. Negative coordinates
     * and overflow are handled.
     *
     * @param  string $sText
     * @param  int    $iX
     * @param  int    $iY
     * @param  int    $iMaxX - If < 1, the maximum X ordinate of the display is used
     * @return self
     */
    public function writeTextSpan(string $sText, int $iX, int $iY, int $iMaxX = 0) : self;

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
