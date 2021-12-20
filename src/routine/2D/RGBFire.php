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

use \SPLFixedArray;

/**
 * Random plasma fire effect. Burns upwards from the lower extent of the display.
 */
class RGBFire extends Base {

    const DEFAULT_PARAMETERS = [
        'fPhase1Rate' => 1.0,    // Wax and wane is controlled by two interfering sine waves
        'fPhase1Amp'  => 32.0,
        'fPhase1Base' => 64.0,
        'fPhase2Rate' => 1.1,
        'fPhase2Amp'  => 56.0,
        'fPhase2Base' => 200.0,
        'fPhaseScale' => 6.28,
        'fDecayScale' => 0.6,    // Heat loss rate
        'fMixRatio'   => 10.0    // Persistence effect, ratio of new value to existing value.
    ];

    private array $aPalettePoints = [
        0   => 0x000000,
        86  => 0xFF3300,
        160 => 0xFFFF00,
        255 => 0xFFFFFF
    ];

    private SPLFixedArray $oBuffer, $oPalette;

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay   = $oDisplay;
        $iWidth           = $oDisplay->getWidth();
        $iHeight          = $oDisplay->getHeight();
        $this->oBuffer    = SPLFixedArray::fromArray(array_fill(0, $iWidth * ($iHeight + 1), 0.0));
        $this->oPalette   = (new PDE\Graphics\Palette(256))->gradient($this->aPalettePoints);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if (! $this->oDisplay instanceof PDE\Display\IPixelled) {
            return $this;
        }
        $iWidth  = $this->oDisplay->getWidth();
        $iHeight = $this->oDisplay->getHeight();
        $oPixels = $this->oDisplay->getPixels();

        // Calculate the base line values based on interfering sines
        $iOffset = $iWidth * ($iHeight - 1);
        $fScaleX = $this->oParameters->fPhaseScale / $iWidth;
        for ($x = 0; $x <= $iWidth; ++$x) {
            $fX = $x * $fScaleX;
            $fPhase1 =
                $this->oParameters->fPhase1Base +
                $this->oParameters->fPhase1Amp * sin($fTimeIndex * $this->oParameters->fPhase1Rate + $fX);
            $fPhase2 =
                $this->oParameters->fPhase2Base +
                $this->oParameters->fPhase2Amp * sin($fTimeIndex * $this->oParameters->fPhase2Rate + $fX);
            $this->oBuffer[$iOffset++] = mt_rand((int)min($fPhase1, $fPhase2), (int)max($fPhase1, $fPhase2));
        }

        // Fan the flames up
        $fDecay    = $this->oParameters->fDecayScale;
        $fMixRatio = $this->oParameters->fMixRatio;
        $fMixScale = 1.0 / ($this->oParameters->fMixRatio + 1.0);
        for ($x = 0; $x < $iWidth; ++$x) {
            for ($y = 2; $y < $iHeight; ++$y) {
                // Random value used for both decay amount and direction
                $iRand = mt_rand(0, 8);
                $iFrom = $y * $iWidth + $x;
                $iTo   = $iFrom - $iWidth;
                $fVal  = $this->oBuffer[$iFrom - ($iRand >> 2) + 1] - ($fDecay * $iRand);

                // Blending with previous
                $fVal  = ($fVal * $fMixRatio + $this->oBuffer[$iTo]) * $fMixScale;
                $this->oBuffer[$iTo] = $fVal;

                // Clamp and render
                $iVal  = (int)max(0, $fVal);
                $oPixels[$iTo] = $this->oPalette[$iVal];
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
