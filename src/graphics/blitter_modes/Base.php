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
use ABadCafe\PDE\Graphics\IPixelBuffer;
use \SPLFixedArray;

/**
 * Common base class for IMode implementors
 */
abstract class Base implements IMode {

    /**
     * Lookup array of channel multiplication values
     *
     * @var SPLFixedArray<int>|null $oProducts
     */
    protected static ?SPLFixedArray $oProducts = null;

    /**
     * Constructor
     */
    public function __construct() {
        if (null === self::$oProducts) {
            self::initProducts();
        }
    }

    /**
     * Lookup initialisation
     */
    private static function initProducts(): void {
        self::$oProducts = new SPLFixedArray(65536);
        $iIndex = 0;
        for ($i1 = 0; $i1 < 256; ++$i1) {
            for ($i2 = 0; $i2 < 256; ++$i2) {
                self::$oProducts[$iIndex++] = (($i1 + 0.5) * ($i2 + 0.5)) >> 8;
            }
        }
    }
}
