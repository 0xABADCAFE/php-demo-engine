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

namespace ABadCafe\PDE\Graphics;
use \SPLFixedArray;

/**
 * Fluent blitter
 *
 *     $oBlitter->setSource(...)->setTarget(...)->copy(...)
 */
class Blitter {

    const
        MODE_REPLACE  = 0,
        MODE_INVERSE  = 1,
        MODE_MODULATE = 2,
        MODE_AND      = 3,
        MODE_OR       = 4,
        MODE_XOR      = 5
    ;

    private ?IPixelBuffer $oSource = null, $oTarget = null;

    /**
     * @var BlitterModes\IMode[] $aModes
     */
    private static array $aModes = [];

    private int $iMode = self::MODE_REPLACE;

    private bool
        $bCorrectNegativeTargetX = true,
        $bCorrectNegativeTargetY = true
    ;

    /**
     * Constructor
     */
    public function __construct() {
        if (empty(self::$aModes)) {
            self::$aModes = [
                self::MODE_REPLACE  => new BlitterModes\Replace,
                self::MODE_INVERSE  => new BlitterModes\Inverse,
                self::MODE_MODULATE => new BlitterModes\CombineMultiply,
                self::MODE_AND      => new BlitterModes\CombineAnd,
                self::MODE_OR       => new BlitterModes\CombineOr,
                self::MODE_XOR      => new BlitterModes\CombineXor,

            ];
        }
    }

    /**
     * Fluently set the source for the blit operation.
     *
     * @param  IPixelBuffer $oPixels
     * @return self
     */
    public function setSource(IPixelBuffer $oPixels) : self {
        $this->oSource  = $oPixels;
        return $this;
    }

    /**
     * Set the render mode. Unknown modes will be interpreted as MODE_REPLACE
     *
     * @param  int $iMode
     * @return self
     */
    public function setMode(int $iMode) : self {
        $iMode = isset(self::$aModes[$iMode]) ? $iMode : self::MODE_REPLACE;
        $this->iMode = $iMode;
        return $this;
    }

    /**
     * Controls how the source area is determined when the target coordinates are negative.
     *
     * When set to true, copying to a decreasingly negative coordinate results in a scrolling into view.
     * When set to false, copying to a decreasingly negative coordinate results in a reveal.
     *
     * @param  bool $bX
     * @param  bool $bY
     * @return self
     */
    public function setNegativeTargetBehaviour(bool $bX, bool $bY) : self {
        $this->bCorrectNegativeTargetX = $bX;
        $this->bCorrectNegativeTargetY = $bY;
        return $this;
    }

    /**
     * Fluently set the target for the blit operation.
     *
     * @param  IPixelBuffer $oPixels
     * @return self
     */
    public function setTarget(IPixelBuffer $oPixels) : self {
        $this->oTarget  = $oPixels;
        return $this;
    }

    /**
     * Perform a copy. This handles all the necessary cropping and other checks, then delegates the final
     * operation to the IMode implementation directed by the current mode.
     *
     * @param  int $iSourceX,
     * @param  int $iSourceY,
     * @param  int $iTargetX,
     * @param  int $iTargetY,
     * @param  int $iWidth,
     * @param  int $iHeight
     * @return self
     */
    public function copy(
        int $iSourceX, int $iSourceY,
        int $iTargetX, int $iTargetY,
        int $iWidth,   int $iHeight
    ) : self {
        if (!$this->oSource || !$this->oTarget) {
            throw new \Exception();
        }

        $iSourceW = $this->oSource->getWidth();
        $iSourceH = $this->oSource->getHeight();
        $iTargetW = $this->oTarget->getWidth();
        $iTargetH = $this->oTarget->getHeight();

        // Check for totally out of bounds cases that we can just early out
        if (
            $iWidth < 1 || $iHeight < 1  ||
            $iSourceX >= $iSourceW ||
            $iSourceY >= $iSourceH ||
            $iTargetX >= $iTargetW ||
            $iTargetY >= $iTargetH
        ) {
            return $this;
        }

        // When we wish to plot the image off the negative ends of the display, we
        // need to update the source coordinates too. Unless we want a wipe effect.
        if ($this->bCorrectNegativeTargetX && $iTargetX < 0) {
            $iSourceX -= $iTargetX;
        }
        if ($this->bCorrectNegativeTargetY && $iTargetY < 0) {
            $iSourceY -= $iTargetY;
        }

        // Crop to the Target
        if (!($oCropped = $this->cropRectangleToArea(
            $iTargetX, $iTargetY,
            $iWidth,   $iHeight,
            $iTargetW, $iTargetH
        ))) {
            return $this;
        }

        $iTargetX = $oCropped->iRectX;
        $iTargetY = $oCropped->iRectY;
        $iWidth   = $oCropped->iRectW;
        $iHeight  = $oCropped->iRectH;

        // Crop to the Source
        if (!($oCropped = $this->cropRectangleToArea(
            $iSourceX, $iSourceY,
            $iWidth,   $iHeight,
            $iSourceW, $iSourceH
        ))) {
            return $this;
        }

        $iSourceX = $oCropped->iRectX;
        $iSourceY = $oCropped->iRectY;
        $iWidth   = $oCropped->iRectW;
        $iHeight  = $oCropped->iRectH;

        self::$aModes[$this->iMode]->copy(
            $this->oSource, $this->oTarget,
            $iSourceX, $iSourceY,
            $iTargetX, $iTargetY,
            $iWidth,   $iHeight
        );

        return $this;
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
