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

    /**
     * Define the display format in terms of the supported Drawing Modes
     */
    const
        // Fixed ASCII on fixed background
        FORMAT_ASCII         = Graphics\IDrawMode::ASCII    |
                               Graphics\IDrawMode::BG_FIXED |
                               Graphics\IDrawMode::BG_FIXED,

        // RGB only (no ASCII)
        FORMAT_RGB           = Graphics\IDrawMode::FG_FIXED |
                               Graphics\IDrawMode::BG_RGB,

         // RGB ASCII on fixed background
        FORMAT_RGB_ASCII     = Graphics\IDrawMode::ASCII    |
                               Graphics\IDrawMode::FG_RGB   |
                               Graphics\IDrawMode::BG_FIXED,

        // Fixed ASCII on RGB background
        FORMAT_ASCII_RGB     = Graphics\IDrawMode::ASCII    |
                               Graphics\IDrawMode::FG_FIXED |
                               Graphics\IDrawMode::BG_RGB,

        // RGB ASCII on RGB background
        FORMAT_RGB_ASCII_RGB = Graphics\IDrawMode::ASCII    |
                               Graphics\IDrawMode::FG_RGB   |
                               Graphics\IDrawMode::BG_RGB
    ;

    /**
     * Query the format of the display. Returns an integer matching one of the FORMAT_ constants above.
     *
     * @return int
     */
    public function getFormat(): int;

    /**
     * Returns a fixed length array of integer values that represent the pixels. The size of the array
     * is the product of the display's width and height.
     *
     * @return SPLFixedArray<int>
     */
    public function getPixels(): SPLFixedArray;

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
    public function setRGBWriteMask(int $iMask): self;

    /**
     * @return int
     */
    public function getRGBWriteMask(): int;
}
