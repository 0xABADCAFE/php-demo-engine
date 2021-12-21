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
use function \array_fill_keys, \base_convert, \range, \sprintf;

/**
 * BasicRGB
 *
 * Supports a character cell resolution RGB. Renders synchronously
 */
class BasicRGB extends Base implements IPixelled {

    const DEFAULT_PARAMETERS = [
        /**
         * Default writemask code (hex)
         */
        'sMaskRGB'     => 'FFFFFF'
    ];

    use TPixelled, TInstrumented;

    /** @var string[] $aLineBreaks */
    private array $aLineBreaks = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);
        $this->initPixelBuffer($iWidth, $iHeight, self::FORMAT_RGB, 0);
        $aLineBreaks   = range(0, $iWidth * $iHeight, $iWidth);
        unset($aLineBreaks[0]);
        $this->aLineBreaks = array_fill_keys($aLineBreaks, "\n");
        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::CRSR_ON;
        $this->reportRedraw();
    }

    /**
     * @inheritDoc
     */
    public function clear(): self {
        $this->resetPixelBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw(): self {
        $this->beginRedraw();
        $sRawBuffer = IANSIControl::CRSR_TOP_LEFT . sprintf(IANSIControl::ATTR_BG_RGB_TPL, 0, 0, 0);
        $iLastRGB  = 0;
        $sTemplate = IANSIControl::ATTR_BG_RGB_TPL . ' ';
        foreach ($this->oPixels as $j => $iRGB) {
            $iRGB &= $this->iRGBWriteMask;
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

    /**
     * @inheritDoc
     */
    public function setParameters(array $aParameters): self {
        $oParameters = $this->filterRawParameters($aParameters);
        if (isset($oParameters->sMaskRGB)) {
            $this->setRGBWriteMask((int)base_convert($oParameters->sMaskRGB, 16, 10));
        }
        return $this;
    }
}
