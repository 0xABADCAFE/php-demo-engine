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

use \SPLFixedArray;

/**
 * IDisplay
 */
interface IDisplay {

    const
        I_MIN_WIDTH  = 40,
        I_MIN_HEIGHT = 20
    ;

    /**
     * Constructor. We expect to be told the basic dimensions.
     *
     * @param int $iWidth
     * @param int $iHeight
     */
    public function __construct(int $iWidth, int $iHeight);

    /**
     * Reset the display. This may involce issuing various escape sequences to resize the terminal and clear it.
     *
     * @return self - fluent
     */
    public function reset() : self;

    /**
     * Get the pixel width
     *
     * @return int
     */
    public function getWidth() : int;

    /**
     * Get the span width. This may be the same as the pixel width, or larger.
     *
     * @return int
     */
    public function getSpanWidth() : int;

    /**
     * Get the pixel height
     *
     * @return int
     */
    public function getHeight() : int;

    /**
     * Clear
     *
     * @return self
     */
    public function clear() : self;

    /**
     * Use refresh after updating the PixelArray.
     *
     * @return self
     */
    public function refresh() : self;

    /**
     * Use redraw to just repaint whatever is in the raw buffer.
     *
     * @return self
     */
    public function redraw() : self;

    /**
     * Get the Pixel Array, aka nOOb mode, lol.
     *
     * @return SPLFixedArray
     */
    public function getPixels() : SPLFixedArray;

    /**
     * Get the raw display buffer, aka 1337 mode, lol
     *
     * Modifications made here can be rendered by a call to redraw().
     * Calling refresh() is likely to destroy.
     *
     * @return string&
     */
    public function &getRaw() : string;

    /**
     * @return int
     */
    public function getMaxRawLuma() : int;
}
