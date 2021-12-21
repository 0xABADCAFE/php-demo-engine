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
class Inverse implements IMode {

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
                $oTargetP[$iOffset] = ~$oTargetP[$iOffset];
                ++$iOffset;
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
                $oTargetP[$iTargetIndex++] = ~$oSourceP[$iSourceIndex++];
            }
            $iSourceIndex += $iSourceSpan;
            $iTargetIndex += $iTargetSpan;
        }
    }
}
