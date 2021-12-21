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
 * IMode implementation for MODE_REPLACE
 */
class Replace extends Base {

    /**
     * @inheritDoc
     */
    public function fill(
        IPixelBuffer $oTarget,
        int $iValue,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ): void {
        $iTargetW = $oTarget->getWidth();
        $iOffset  = $iTargetW * $iTargetY + $iTargetX;
        $iSpan    = $iTargetW - $iWidth;
        $oTargetP = $oTarget->getPixels();
        while ($iHeight--) {
            $i = $iWidth;
            while ($i--) {
                $oTargetP[$iOffset++] = $iValue;
            }
            $iOffset += $iSpan;
        }
    }

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
    ): void {
        $iSourceW = $oSource->getWidth();
        $iTargetW = $oTarget->getWidth();
        $oSourceP = $oSource->getPixels();
        $oTargetP = $oTarget->getPixels();

        $iSourceIndex = $iSourceY * $iSourceW + $iSourceX;
        $iTargetIndex = $iTargetY * $iTargetW + $iTargetX;
        $iSourceSpan  = $iSourceW - $iWidth;
        $iTargetSpan  = $iTargetW - $iWidth;

        while ($iHeight--) {
            $i = $iWidth;
            while ($i--) {
                $oTargetP[$iTargetIndex++] = $oSourceP[$iSourceIndex++];
            }
            $iSourceIndex += $iSourceSpan;
            $iTargetIndex += $iTargetSpan;
        }
    }

    /**
     * @inheritDoc
     */
    public function copyAlpha(
        IPixelBuffer $oSource,
        IPixelBuffer $oTarget,
        int $iSourceX,
        int $iSourceY,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ): void {
        $iSourceW = $oSource->getWidth();
        $iTargetW = $oTarget->getWidth();
        $oSourceP = $oSource->getPixels();
        $oTargetP = $oTarget->getPixels();
        while ($iHeight--) {
            $iPixels      = $iWidth;
            $iSourceIndex = $iSourceX + $iSourceY++ * $iSourceW;
            $iTargetIndex = $iTargetX + $iTargetY++ * $iTargetW;
            while ($iPixels--) {
                $iSourcePixel = $oSourceP[$iSourceIndex++];
                $iTargetPixel = $oTargetP[$iTargetIndex];

                $iAlphaIndex  = ($iSourcePixel >> 16) & 0xFF00;
                $iSourceRGB   =
                    (self::$oProducts[$iAlphaIndex|($iSourcePixel & 0xFF)]) |
                    (self::$oProducts[$iAlphaIndex|(($iSourcePixel & 0xFF00) >> 8)] << 8) |
                    (self::$oProducts[$iAlphaIndex|(($iSourcePixel & 0xFF0000) >> 16)] << 16)
                ;

                $iAlphaIndex = 0xFF00 - $iAlphaIndex;
                $iTargetRGB   =
                    (self::$oProducts[$iAlphaIndex|($iTargetPixel & 0xFF)]) |
                    (self::$oProducts[$iAlphaIndex|(($iTargetPixel & 0xFF00) >> 8)] << 8) |
                    (self::$oProducts[$iAlphaIndex|(($iTargetPixel & 0xFF0000) >> 16)] << 16)
                ;

                $oTargetP[$iTargetIndex++] = $iSourceRGB + $iTargetRGB;
            }
        }
    }
}
