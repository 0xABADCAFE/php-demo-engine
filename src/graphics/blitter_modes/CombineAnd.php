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
 * IMode implementation for MODE_AND
 */
class CombineAnd implements IMode {

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
                $oTarget[$iOffset++] &= $iValue;
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
        while ($iHeight--) {
            $iPixels      = $iWidth;
            $iSourceIndex = $iSourceX + $iSourceY++ * $iSourceW;
            $iTargetIndex = $iTargetX + $iTargetY++ * $iTargetW;
            while ($iPixels--) {
                $oTarget[$iTargetIndex++] &= $oSource[$iSourceIndex++];
            }
        }
    }
}
