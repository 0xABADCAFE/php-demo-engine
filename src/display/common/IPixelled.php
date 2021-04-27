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
use ABadCafe\PDE\Graphics;
use \SPLFixedArray;

/**
 * IPixelled
 *
 * Interface for displays that model some sort of basic pixelling. This will typically involve a lot of
 * cheating around with ANSI escape sequences to simulate a (very blocky) bitmap.
 */
interface IPixelled extends Graphics\IPixelBuffer {

    const
        FORMAT_ASCII         = 0, // Fixed ASCII on fixed background
        FORMAT_RGB           = 1, // RGB only (no ASCII)
        FORMAT_RGB_ASCII     = 2, // RGB ASCII on fixed background
        FORMAT_ASCII_RGB     = 3, // Fixed ASCII on RGB background
        FORMAT_RGB_ASCII_RGB = 4  // RGB ASCII on RGB background
    ;

    /**
     * Query the format of the pixel. Returns an integer matching one of the PIX_ constants.
     *
     * @return int
     */
    public function getPixelFormat() : int;

    /**
     * Returns a fixed length array of integer values that represent the pixels. The size of the array
     * is the product of the display's width and height.
     *
     * @return SPLFixedArray
     */
    public function getPixels() : SPLFixedArray;

    /**
     * Set an RGB write mask to use. This is an integer value that will be bitwise masked against the
     * RGB value before displaying. This can be used for various effects, such as colour reduction (posterise)
     * channel selection, etc.
     *
     * Examples:
     *     Posterise: (2 bits per gun) 0xC0C0C0
     *     Blue Filter                 0x0000FF
     *     Green Filter                0x00FF00
     *     Red Filter                  0xFF0000
     *     Simulate RGB565             0xF8FCF8
     *
     * @param  int  $iMask
     * @return self
     */
    public function setRGBWriteMask(int $iMask) : self;

    /**
     * @return int
     */
    public function getRGBWriteMask() : int;
}
