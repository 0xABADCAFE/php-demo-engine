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

/**
 * IMode implementation for MODE_MODULATE
 */
class CombineMultiply implements IMode {

    static string $sProducts = '';

//     public function __construct() {
//         if (empty(self::$sProducts)) {
//             self::buildProducts();
//         }
//     }

    /**
     * @inheritDoc
     */
    public function copy(
        IPixelBuffer $oSource,
        IPixelBuffer $oTarget,
        int $iSourceX,
        int $iSourceY,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ) {
        $iSourceW = $oSource->getWidth();
        $iTargetW = $oTarget->getWidth();
        $oSource  = $oSource->getPixels();
        $oTarget  = $oTarget->getPixels();
        while ($iHeight--) {
            $iPixels      = $iWidth;
            $iSourceIndex = $iSourceX + $iSourceY++ * $iSourceW;
            $iTargetIndex = $iTargetX + $iTargetY++ * $iTargetW;
            while ($iPixels--) {
                $iSource = $oSource[$iSourceIndex++];
                $iTarget = $oTarget[$iTargetIndex];

                //$iBlue  = ord(self::$sProducts[(($iSource & 0xFF) << 8)     | ($iTarget & 0xFF)]);
                //$iGreen = ord(self::$sProducts[($iSource & 0xFF00)          | (($iTarget & 0xFF00) >> 8)]);
                //$iRed   = ord(self::$sProducts[(($iSource & 0xFF0000) >> 8) | (($iTarget & 0xFF0000) >> 16)]);

                $iRed   = ((($iSource >> 16) & 0xFF) * (($iTarget >> 16) & 0xFF)) >> 8;
                $iGreen = ((($iSource >> 8)  & 0xFF) * (($iTarget >> 8)  & 0xFF)) >> 8;
                $iBlue  = ((($iSource & 0xFF) * ($iTarget & 0xFF))) >> 8;

                $oTarget[$iTargetIndex++] = ($iRed << 16) | ($iGreen << 8) | $iBlue;
            }
        }
    }

//     private static function buildProducts() {
//         self::$sProducts = str_repeat(' ', 65536);
//         $iIndex = 0;
//         for ($i1 = 0; $i1 < 256; ++$i1) {
//             for ($i2 = 0; $i2 < 256; ++$i2) {
//                 self::$sProducts[$iIndex++] = chr( ($i1 * $i2) >> 8 );
//             }
//         }
//     }
}
