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
 * RGBPersistence
 *
 * Blends frames with previous ones in a decaying fashion to produce a form or motion blur.
 */
class RGBPersistence extends Base {

    const DEFAULT_PARAMETERS = [
        'iStrength' => 0
    ];

    private SPLFixedArray $oLastBuffer;

    /**
     * @inheritDoc
     *
     * Overridden from base class to capture the current buffer contents
     * of the display for the blend buffer.
     */
    public function enable(int $iFrameNumber, float $fTimeIndex): self {
        parent::enable($iFrameNumber, $fTimeIndex);
        if ($this->bEnabled) {
            $this->oLastBuffer = clone $this->oDisplay->getPixels();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        if ($this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled)) {
            $this->oLastBuffer = clone $oDisplay->getPixels();
        }
        $this->oDisplay = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        if (! $this->oDisplay instanceof PDE\Display\IPixelled) {
            return $this;
        }

        $oPixels = $this->oDisplay->getPixels();
        $oLast   = $this->oLastBuffer;

        $iHalfMask =  0x7F7F7F;
        $iQrtrMask =  0x3F3F3F;

        if ($this->oDisplay->getFormat() & PDE\Graphics\IDrawMode::FG_RGB) {
            $iHalfMask |= $iHalfMask << 24;
            $iQrtrMask |= $iQrtrMask << 24;
        }

        // Dosage!
        switch ($this->oParameters->iStrength) {
            case 2:
                // 75% previous / 25% current
                foreach ($oLast as $i => $iRGB) {
                    $iHalfRGB    = ($iRGB >> 1)        & $iHalfMask;
                    $iQrtrRGB    = ($iHalfRGB >> 1)    & $iQrtrMask;
                    $iLastRGB    = ($oPixels[$i] >> 2) & $iQrtrMask;
                    $oLast[$i]   = $oPixels[$i] = $iHalfRGB + $iQrtrRGB + $iLastRGB;
                }
                break;
            case 1:
                // 50% previous / 50% current
                foreach ($oPixels as $i => $iRGB) {
                    $iHalfRGB    = ($iRGB >> 1)      & $iHalfMask;
                    $iLastRGB    = ($oLast[$i] >> 1) & $iHalfMask;
                    $oLast[$i]   = $oPixels[$i] = $iHalfRGB + $iLastRGB;
                }
                break;
            default:
                // 25% previous / 75% current
                foreach ($oPixels as $i => $iRGB) {
                    $iHalfRGB    = ($iRGB >> 1)      & $iHalfMask;
                    $iQrtrRGB    = ($iHalfRGB >> 1)  & $iQrtrMask;
                    $iLastRGB    = ($oLast[$i] >> 2) & $iQrtrMask;
                    $oLast[$i]   = $oPixels[$i] = $iHalfRGB + $iQrtrRGB + $iLastRGB;
                }
                break;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {

    }
}
