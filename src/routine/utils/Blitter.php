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


namespace ABadCafe\PDE\Routine\Utils;
use \SPLFixedArray;

/**
 * Fluent blitter
 *
 *     $oBlitter->setSource(...)->setTarget(...)->copy(...)
 */
class Blitter {

    private ?SPLFixedArray $oSource = null, $oTarget = null;

    private int
        $iSourceW = 0,
        $iSourceH = 0,
        $iTargetW = 0,
        $iTargetH = 0
    ;

    private bool
        $bCorrectNegativeTargetX = true,
        $bCorrectNegativeTargetY = true
    ;

    /**
     * Fluently set the source for the blit operation.
     *
     * @param  SPLFixedArray $oPixels
     * @param  int           $iWidth
     * @param  int           $iHeight
     * @return self
     */
    public function setSource(SPLFixedArray $oPixels, int $iWidth, int $iHeight) : self {
        if ($iWidth < 1 || $iHeight < 1) {
            throw new \RangeException('Illegal dimensions');
        }
        $this->oSource  = $oPixels;
        $this->iSourceW = $iWidth;
        $this->iSourceH = $iHeight;
        return $this;
    }

    public function enableNegativeTargetX() : self {
        $this->bCorrectNegativeTargetX = true;
        return $this;
    }

    public function enableNegativeTargetY() : self {
        $this->bCorrectNegativeTargetY = true;
        return $this;
    }

    public function disableNegativeTargetX() : self {
        $this->bCorrectNegativeTargetX = false;
        return $this;
    }

    public function disableNegativeTargetY() : self {
        $this->bCorrectNegativeTargetY = false;
        return $this;
    }

    /**
     * Fluently set the target for the blit operation.
     *
     * @param  SPLFixedArray $oPixels
     * @param  int           $iWidth
     * @param  int           $iHeight
     * @return self
     */
    public function setTarget(SPLFixedArray $oPixels, int $iWidth, int $iHeight) : self {
        if ($iWidth < 1 || $iHeight < 1) {
            throw new \RangeException('Illegal dimensions');
        }
        $this->oTarget  = $oPixels;
        $this->iTargetW = $iWidth;
        $this->iTargetH = $iHeight;
        return $this;
    }

    /**
     * Perform a copy
     */
    public function copy(
        int $iSourceX, int $iSourceY,
        int $iTargetX, int $iTargetY,
        int $iWidth,   int $iHeight
    ) : void {
        if (!$this->oSource || !$this->oTarget) {
            throw new \Exception();
        }

        // Check for totally out of bounds cases that we can just early out
        if (
            $iWidth < 1 || $iHeight < 1  ||
            $iSourceX >= $this->iSourceW ||
            $iSourceY >= $this->iSourceH ||
            $iTargetX >= $this->iTargetW ||
            $iTargetY >= $this->iTargetH
        ) {
            return;
        }

        // When we wish to plot the image off the negative ends of the display, we
        // need to update the start c
        if ($this->bCorrectNegativeTargetX && $iTargetX < 0) {
            $iSourceX -= $iTargetX;
        }
        if ($this->bCorrectNegativeTargetY && $iTargetY < 0) {
            $iSourceY -= $iTargetY;
        }

        $oCropped = $this->cropRectangleToArea(
            $iTargetX, $iTargetY,
            $iWidth, $iHeight,
            $this->iTargetW, $this->iTargetH
        );

        if (!$oCropped) {
            return;
        } else {
            $iTargetX = $oCropped->iRectX;
            $iTargetY = $oCropped->iRectY;
            $iWidth   = $oCropped->iRectW;
            $iHeight  = $oCropped->iRectH;
        }

        $oCropped = $this->cropRectangleToArea(
            $iSourceX, $iSourceY,
            $iWidth, $iHeight,
            $this->iSourceW, $this->iSourceH
        );

        if (!$oCropped) {
            return;
        } else {
            $iSourceX = $oCropped->iRectX;
            $iSourceY = $oCropped->iRectY;
            $iWidth   = $oCropped->iRectW;
            $iHeight  = $oCropped->iRectH;
        }

        while ($iHeight--) {
            $iPixels      = $iWidth;
            $iSourceIndex = $iSourceX + $iSourceY++ * $this->iSourceW;
            $iTargetIndex = $iTargetX + $iTargetY++ * $this->iTargetW;
            while ($iPixels--) {
                $this->oTarget[$iTargetIndex++] = $this->oSource[$iSourceIndex++];
            }
        }

    }

    /**
     * Crop a rectangle (x, y, w, h) to a given area restriction (w, h). Returns a tuple of the cropped rectangle
     * dimensions, or null if there is no intersection between the rectangle and the area.
     *
     * @param  int $iRectX
     * @param  int $iRectY
     * @param  int $iRectW
     * @param  int $iRectH
     * @param  int $iAreaW
     * @param  int $iAreaH
     * @return object|null  { int $iRectX, int $iRectY, int $iRectW, int $iRectH }
     */
    protected function cropRectangleToArea(int $iRectX, int $iRectY, int $iRectW, int $iRectH, int $iAreaW, int $iAreaH) : ?object {
        // Crop copy rectangle against target dimensions
        if ($iRectX < 0) {
            // Crop Left
            if (($iRectW += $iRectX) < 1) {
                return null;
            }
            $iRectX = 0;
        }
        if (($iRectX + $iRectW) > $iAreaW) {
            // Crop Right
            if (($iRectW -= ($iRectX + $iRectW - $iAreaW)) < 1) {
                return null;
            }
        }
        if ($iRectY < 0) {
            // Crop Top
            if (($iRectH += $iRectY) < 1) {
                return null;
            }
            $iRectY = 0;
        }
        if (($iRectY + $iRectH) > $iAreaH) {
            // Crop bottom
            if (($iRectH -= ($iRectY + $iRectH - $iAreaH)) < 1) {
                return null;
            }
        }

        return (object)[
            'iRectX' => $iRectX,
            'iRectY' => $iRectY,
            'iRectW' => $iRectW,
            'iRectH' => $iRectH,
        ];
    }
}
