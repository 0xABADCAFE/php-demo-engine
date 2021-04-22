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

namespace ABadCafe\PDE\Graphics\BlitterModes;
use \SPLFixedArray;

/**
 * Common base class for IMode implementors
 */
abstract class Base implements IMode {

    /**
     *
     */
    protected static ?SPLFixedArray $oProducts = null;

    public function construct() {
        if (null === self::$oProducts) {
            self::initProducts();
        }
    }

    private static function initProducts() : void {
        self::$oProducts = new SPLFixedArray(65536);
        $iIndex = 0;
        for ($i1 = 0; $i1 < 256; ++$i1) {
            for ($i2 = 0; $i2 < 256; ++$i2) {
                self::$oProducts[$iIndex++] = (($i1 + 0.5) * ($i2 + 0.5)) >> 8;
            }
        }
    }
}
