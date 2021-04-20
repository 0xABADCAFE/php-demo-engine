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

namespace ABadCafe\PDE\Display;
use ABadCafe\PDE;
use \SPLFixedArray;

/**
 * TPixelled
 *
 * A simple mixin for use with the IPixelled interface
 */
trait TPixelled {

    private SPLFixedArray $oPixels, $oNewPixels;

    private int $iPixelFormat, $iRGBWriteMask = 0xFFFFFF;

    /**
     * @inheritDoc
     */
    private function initPixelBuffer(int $iWidth, int $iHeight, int $iPixelFormat) {
        $this->oPixels      = clone // drop through
        $this->oNewPixels   = SPLFixedArray::fromArray(array_fill(0, $iWidth * $iHeight, 0));
        $this->iPixelFormat = $iPixelFormat;
    }


    /**
     * @inheritDoc
     */
    private function resetPixelBuffer() {
        $this->oPixels = clone $this->oNewPixels;
    }

    /**
     * @inheritDoc
     */
    public function getPixelFormat() : int {
        return $this->iPixelFormat;
    }

    /**
     * @inheritDoc
     */
    public function getPixels() : SPLFixedArray {
        return $this->oPixels;
    }

    /**
     * @inheritDoc
     */
    public function setRGBWriteMask(int $iMask) : self {
        $this->iRGBWriteMask = $iMask;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRGBWriteMask() : int {
        return $this->iRGBWriteMask;
    }
}
