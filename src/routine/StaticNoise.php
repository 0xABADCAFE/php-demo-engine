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
 * StaticNoise
 *
 * Simple text mode white noise
 */
class StaticNoise implements PDE\IRoutine {

    use TRoutine;

    const DEFAULT_PARAMETERS = [
        'iBorderH' => 0,
        'iBorderV' => 0
    ];

    /**
     * @see ICustomChars::MAP
     */
    const NOISE_CHARS = "\x96\x97\x98\x99\x9A\x9B\x9C\x9D\x9E\x9F";
    const MAX_CHAR    = 9;

    private int $iWidth, $iHeight;

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->oDisplay = $oDisplay;
        $this->iWidth   = $oDisplay->getWidth();
        $this->iHeight  = $oDisplay->getHeight();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->bEnabled && $this->oDisplay instanceof PDE\Display\IASCIIArt) {
            $sCharBuffer = &$this->oDisplay->getCharacterBuffer();
            $iSpan       = $this->oDisplay->getCharacterWidth();\s
            $iMaxY       = $this->iHeight - $this->oParameters->iBorderV;
            $iMaxX       = $this->iWidth  - $this->oParameters->iBorderH;
            for ($iYPos = $this->oParameters->iBorderV; $iYPos < $iMaxY; ++$iYPos) {
                for ($iXPos = $this->oParameters->iBorderH; $iXPos < $iMaxX; ++$iXPos) {
                    $iBufferPos = $iXPos + $iSpan * $iYPos;
                    $sCharBuffer[$iBufferPos] = self::NOISE_CHARS[mt_rand(0, self::MAX_CHAR)];
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
