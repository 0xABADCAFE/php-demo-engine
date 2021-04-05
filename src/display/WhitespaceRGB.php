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
 * WhitespaceRGB
 */
class WhitespaceRGB implements PDE\IDisplay {

    const INIT  = "\x1b[2J";
    const RESET = "\x1b[2H";

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

    /**
     * @inheritDoc
     */
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
        return $this->redraw();
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $this->sRawBuffer = self::RESET;
        $iLastRGB = 0;
        foreach ($this->oPixels as $j => $iRGB) {
            if ($j && 0 == ($j % $this->iWidth)) {
                $this->sRawBuffer .= "\n";
            }
            if ($iRGB !== $iLastRGB) {
                $this->sRawBuffer .= sprintf(
                    "\x1b[48;2;%d;%d;%dm ",
                    ($iRGB >> 16) & 0xFF, // Red
                    ($iRGB >> 8) & 0xFF, // Green
                    ($iRGB & 0xFF)        // Blue
                );
                $iLastRGB = $iRGB;
            } else {
                $this->sRawBuffer .= ' ';
            }
        }
        $this->sRawBuffer .= "\n";
        echo self::$this->sRawBuffer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPixels() : SPLFixedArray {
        return $this->oPixels;
    }
}
