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
 * IDisplay
 *
 * A basic fluent interface for display implementors. This does not make any assumption about what the display device
 * is, other than that it a raster displays.
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
     * Render the frame.
     *
     * @return self
     */
    public function redraw() : self;

    /**
     * Waits for the frame to be drawn. The primary use for this is when
     * switching between synchronous and asynchronous modes of rendering.
     *
     * @return self
     */
    public function waitForFrame() : self;

}
