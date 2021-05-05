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
use ABadCafe\PDE;
use \SPLFixedArray;

/**
 * ASCIIOverRGB
 *
 * ASCII with fixed foreground colour over RGB background. All of the implementation is present in the base
 * class, this just defines some necessary properties for it to work.
 */
class ASCIIOverRGB extends BaseAsyncASCIIWithRGB {

    const
        ATTR_TEMPLATE = IANSIControl::ATTR_BG_RGB_TPL, // ANSI template for setting the RGB value
        DATA_FORMAT   = self::DATA_FORMAT_32,          // Data transfer size
        PIXEL_FORMAT  = self::FORMAT_ASCII_RGB
    ;

    protected function getDefaultPixelValue() : int {
        return $this->iBGColour;
    }
}
