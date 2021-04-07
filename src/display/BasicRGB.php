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
 * BasicRGB
 */
class BasicRGB implements PDE\IDisplay, IPixelled {

    private int           $iWidth, $iHeight;
    private SPLFixedArray $oPixels, $oNewPixels;

    private array $aLineBreaks = [];

    private int   $iTotalRedrawCount = 0;
    private float $fTotalRedrawTime  = 0.0;

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

        $aLineBreaks   = range(0, $iWidth * $iHeight, $iWidth);
        unset($aLineBreaks[0]);
        $this->aLineBreaks = array_fill_keys($aLineBreaks, "\n");
        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::CRSR_ON, "\n";
        printf(
            "Total Redraw Time: %.3f seconds, %.2f ms/redraw\n",
            $this->fTotalRedrawTime,
            1000.0 * $this->fTotalRedrawTime / $this->iTotalRedrawCount
        );
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        printf(IANSIControl::TERM_SIZE_TPL, $this->iHeight + 2, $this->iWidth + 1);
        $this->clear();
        echo IANSIControl::TERM_CLEAR . IANSIControl::CRSR_OFF;
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
        $fMark = microtime(true);
        $sRawBuffer = IANSIControl::CRSR_TOP_LEFT;
        $iLastRGB  = 0;
        $sTemplate = IANSIControl::ATTR_BG_RGB_TPL . ' ';
        foreach ($this->oPixels as $j => $iRGB) {
            $sRawBuffer .= $this->aLineBreaks[$j] ?? '';
            if ($iRGB !== $iLastRGB) {
                $sRawBuffer .= sprintf(
                    $sTemplate,
                    ($iRGB >> 16) & 0xFF, // Red
                    ($iRGB >> 8) & 0xFF,  // Green
                    ($iRGB & 0xFF)        // Blue
                );
                $iLastRGB = $iRGB;
            } else {
                $sRawBuffer .= ' ';
            }
        }
        echo $sRawBuffer . IANSIControl::ATTR_RESET . "\n";
        $this->fTotalRedrawTime += microtime(true) - $fMark;
        ++$this->iTotalRedrawCount;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPixelFormat() : int {
        return self::PIX_FORMAT_XRGB;
    }

    /**
     * @inheritDoc
     */
    public function getPixelBuffer() : SPLFixedArray {
        return $this->oPixels;
    }
}
