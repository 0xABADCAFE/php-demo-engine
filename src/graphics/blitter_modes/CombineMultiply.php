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

    public function fill(
        IPixelBuffer $oTarget,
        int $iValue,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ) {
        $iTargetW = $oTarget->getWidth();
        $iOffset  = $iTargetW * $iTargetY + $iTargetX;
        $iSpan    = $iTargetW - $iWidth;
        $oTargetP = $oTarget->getPixels();

        $iSourceR = ($iValue >> 16) & 0xFF;
        $iSourceG = ($iValue >> 8)  & 0xFF;
        $iSourceB = ($iValue & 0xFF);

        while ($iHeight--) {
            $i = $iWidth;
            while ($i--) {
                $iTarget = $oTarget[$iOffset];
                $iRed    = ($iSourceR * (($iTarget >> 16) & 0xFF)) >> 8;
                $iGreen  = ($iSourceG * (($iTarget >> 8)  & 0xFF)) >> 8;
                $iBlue   = ($iSourceB * ($iTarget & 0xFF)) >> 8;
                $oTargetP[$iOffset++] = ($iRed << 16) | ($iGreen << 8) | $iBlue;
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
    ) {
        $iSourceW = $oSource->getWidth();
        $iTargetW = $oTarget->getWidth();
        $oSourceP = $oSource->getPixels();
        $oTargetP = $oTarget->getPixels();

        $iSourceIndex = $iSourceY * $iSourceW + $iSourceX;
        $iTargetIndex = $iTargetY * $iTargetW + $iTargetX;
        $iSourceSpan  = $iSourceW - $iWidth;
        $iTargetSpan  = $iTargetW - $iWidth;

        while ($iHeight--) {
            $i     = $iWidth;
            $iMask = 0xFFFFFF;
            while ($i--) {
                $iSource  = $oSourceP[$iSourceIndex++];
                $iTarget  = $oTargetP[$iTargetIndex];
                $iProduct = 0;
                if (($iSource & $iMask) && ($iTarget & $iMask)) {
                    $iRed    = ((($iSource >> 16) & 0xFF) * (($iTarget >> 16) & 0xFF)) >> 8;
                    $iGreen  = ((($iSource >> 8)  & 0xFF) * (($iTarget >> 8)  & 0xFF)) >> 8;
                    $iBlue   = ((($iSource & 0xFF) * ($iTarget & 0xFF))) >> 8;
                    $iProduct = ($iRed << 16) | ($iGreen << 8) | $iBlue;
                }
                $oTargetP[$iTargetIndex++] = $iProduct;
            }
            $iSourceIndex += $iSourceSpan;
            $iTargetIndex += $iTargetSpan;
        }
    }
}
