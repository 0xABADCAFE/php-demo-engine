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

class Image implements IPixelBuffer {

    private int $iWidth, $iHeight;
    private SPLFixedArray $oPixels;

    /**
     * Constructor
     *
     * @param int $iWidth
     * @param int $iHeight
     * @param int $iDefaultPixel
     * @throws \RangeException
     */

    public function __construct(int $iWidth, int $iHeight, int $iDefaultPixel = 0) {
        if ($iWidth < 1 || $iHeight < 1) {
            throw new \RangeException();
        }
        $this->iWidth  = $iWidth;
        $this->iHeight = $iHeight;
        $this->oPixels = SPLFixedArray::fromArray(array_fill(0, $iWidth * $iHeight, $iDefaultPixel));
    }

    /**
     * @inheritDoc
     */
    public function getWidth(): int {
        return $this->iWidth;
    }

    /**
     * @inheritDoc
     */
    public function getHeight(): int {
        return $this->iHeight;
    }

    /**
     * @inheritDoc
     */
    public function getPixels(): SPLFixedArray {
        return $this->oPixels;
    }
}
