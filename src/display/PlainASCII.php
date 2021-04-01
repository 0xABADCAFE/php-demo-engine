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
 * PlainASCII
 */
class PlainASCII implements PDE\IDisplay {

    const INIT  = "\x1b[2J";
    const RESET = "\x1b[2H";
    const LUMA  = ' .,-~:;=!*+|%$#@';
    const MAXRL = 15;

    private int           $iWidth, $iHeight;
    private string        $sRawBuffer, $sNewRawBuffer;
    private SPLFixedArray $oPixels, $oNewPixels;

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        if ($iWidth < self::I_MIN_WIDTH || $iHeight < self::I_MIN_HEIGHT) {
            throw new \RangeException('Invalid dimensions');
        }
        $this->iWidth        = $iWidth;
        $this->iHeight       = $iHeight;
        $this->oPixels       = clone // drop through
        $this->oNewPixels    = SPLFixedArray::fromArray(array_fill(0, $iWidth * $iHeight, 0));
        $this->sRawBuffer    = // drop through
        $this->sNewRawBuffer = str_repeat(str_repeat(' ', $iWidth) . "\n", $iHeight);
        $this->reset();
    }

    public function reset() : self {
        printf("\e[8;%d;%dt", $this->iHeight + 2, $this->iWidth + 1);
        $this->clear();
        echo self::INIT;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWidth() : int {
        return $this->iWidth;
    }

    /**
     * @inheritDoc
     */
    public function getSpanWidth() : int {
        return $this->iWidth + 1; // 1 for the newline
    }

    /**
     * @inheritDoc
     */
    public function getHeight() : int {
        return $this->iHeight;
    }

    /**
     * @inheritDoc
     */
    public function clear() : self {
        $this->oPixels    = clone $this->oNewPixels;
        $this->sRawBuffer = $this->sNewRawBuffer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function refresh() : self {
        $i = 0;
        foreach ($this->oPixels as $j => $iValue) {
            if ($j && 0 == ($j % $this->iWidth)) {
                $i++;
            }
            $this->sRawBuffer[$i++] = self::LUMA[($iValue >> 4) & 15];
        }
        return $this->redraw();
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        echo self::RESET, $this->sRawBuffer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPixels() : SPLFixedArray {
        return $this->oPixels;
    }

    public function getRawLuma() : string {
        return self::LUMA;
    }

    /**
     * @inheritDoc
     */
    public function &getRaw() : string {
        return $this->sRawBuffer;
    }

    public function getMaxRawLuma() : int {
        return self::MAXRL;
    }
}
