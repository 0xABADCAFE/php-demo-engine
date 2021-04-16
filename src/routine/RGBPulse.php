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

namespace ABadCafe\PDE\Routine;

use ABadCafe\PDE;

/**
 * Colour pulsating effect based on simple interpolation.
 *
 * TODO controls and optimise
 */
class RGBPulse extends Base {

    const DEFAULT_PARAMETERS = [];

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay   = $oDisplay;
        $this->fXScale    = 255.0 / $this->oDisplay->getWidth();
        $this->fYScale    = 255.0 / $this->oDisplay->getHeight();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            $iWidth  = $this->oDisplay->getWidth();
            $iHeight = $this->oDisplay->getHeight();
            $oPixels = $this->oDisplay->getPixelBuffer();

            $fTScale1 = $this->fYScale * 0.5*(1.0 - cos($fTimeIndex));
            $fTScale2 = $this->fXScale * 0.5*(1.0 - cos($fTimeIndex * 2.0));
            $fTScale3 = $this->fXScale * 0.5*(1.0 - cos($fTimeIndex * 3.0));

            $i = 0;
            for ($y = 0; $y < $iHeight; $y++) {
                for ($x = 0; $x < $iWidth; $x++) {
                    $iRGB = (($y * $fTScale1) & 0xFF) << 8;
                    $iRGB |= (($x * $fTScale2) & 0xFF) << 16;
                    $iRGB |= (($iWidth - $x) * $fTScale3) & 0xFF;
                    $oPixels[$i++] = $iRGB;
                }
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {

    }
}
