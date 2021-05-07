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
 * IMode implementation for MODE_XOR
 */
class CombineXor implements IMode {

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
    ) {
        $iTargetW = $oTarget->getWidth();
        $iOffset  = $iTargetW * $iTargetY + $iTargetX;
        $iSpan    = $iTargetW - $iWidth;
        $oTarget  = $oTarget->getPixels();
        while ($iHeight--) {
            $i = $iWidth;
            while ($i--) {
                $oTarget[$iOffset++] ^= $iValue;
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
        $oSource  = $oSource->getPixels();
        $oTarget  = $oTarget->getPixels();
        $iSourceIndex = $iSourceY * $iSourceW + $iSourceX;
        $iTargetIndex = $iTargetY * $iTargetW + $iTargetX;
        $iSourceSpan  = $iSourceW - $iWidth;
        $iTargetSpan  = $iTargetW - $iWidth;

        while ($iHeight--) {
            $i = $iWidth;
            while ($i--) {
                $oTarget[$iTargetIndex++] &= $oSource[$iSourceIndex++];
            }
            $iSourceIndex += $iSourceSpan;
            $iTargetIndex += $iTargetSpan;
        }
    }
}
