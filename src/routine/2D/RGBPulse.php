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

use function \cos;

/**
 * Colour pulsating effect based on simple interpolation. Requires an IPixelled display.
 *
 */
class RGBPulse extends Base {

    private float $fXScale, $fYScale;

    const DEFAULT_PARAMETERS = [
        'fRate1' => 1.0,
        'fRate2' => 2.0,
        'fRate3' => 3.0
    ];

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        $this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay   = $oDisplay;
        $this->fXScale    = 255.0 / $this->oDisplay->getWidth();
        $this->fYScale    = 255.0 / $this->oDisplay->getHeight();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        if (! $this->oDisplay instanceof PDE\Display\IPixelled) {
            return $this;
        }
        $iWidth  = $this->oDisplay->getWidth();
        $iHeight = $this->oDisplay->getHeight();
        $oPixels = $this->oDisplay->getPixels();

        $fTScale1 = $this->fYScale * 0.5*(1.0 - cos($fTimeIndex * $this->oParameters->fRate1));
        $fTScale2 = $this->fXScale * 0.5*(1.0 - cos($fTimeIndex * $this->oParameters->fRate2));
        $fTScale3 = $this->fXScale * 0.5*(1.0 - cos($fTimeIndex * $this->oParameters->fRate3));

        $i = 0;
        for ($y = 0; $y < $iHeight; $y++) {
            for ($x = 0; $x < $iWidth; $x++) {
                $iRGB = (($y * $fTScale1) & 0xFF) << 8;
                $iRGB |= (($x * $fTScale2) & 0xFF) << 16;
                $iRGB |= (($iWidth - $x) * $fTScale3) & 0xFF;
                $oPixels[$i] = ($oPixels[$i] & 0xFFFFFF000000) | $iRGB;
                ++$i;
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {

    }
}
