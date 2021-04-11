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
 *
 * Supports a character cell resolution RGB. Renders synchronously
 */
class BasicRGB extends Base implements IPixelled {

    use TPixelled, TInstrumented;

    private array $aLineBreaks = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);
        $this->initPixelBuffer($iWidth, $iHeight, self::PIX_RGB);
        $aLineBreaks   = range(0, $iWidth * $iHeight, $iWidth);
        unset($aLineBreaks[0]);
        $this->aLineBreaks = array_fill_keys($aLineBreaks, "\n");
        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::CRSR_ON, "\n";
        $this->reportRedraw();
    }

    /**
     * @inheritDoc
     */
    public function clear() : self {
        $this->resetPixelBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $this->beginRedraw();
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
        echo $sRawBuffer . IANSIControl::ATTR_RESET;
        $this->endRedraw();
        return $this;
    }
}
