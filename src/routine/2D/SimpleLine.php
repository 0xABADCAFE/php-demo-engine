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
use function \str_repeat, \strlen, \urldecode;

/**
 * SimpleLine
 *
 * Lame test effect. Requires an IASCIIArt based display
 */
class SimpleLine extends Base {

    const DEFAULT_PARAMETERS = [
        'iSpacing' => 3,
        'iRate'    => 4,
        'sFill'    => '_'
    ];

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        $this->bCanRender = ($oDisplay instanceof PDE\Display\IASCIIArt);
        $this->oDisplay   = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        if (!$this->oDisplay instanceof PDE\Display\IASCIIArt) {
            return $this;
        }
        $sDrawBuffer = &$this->oDisplay->getCharacterBuffer();
        $sDrawBuffer = '';
        $iWidth      = $this->oDisplay->getWidth();
        $sBlank      = \str_repeat(' ', $iWidth) . "\n";
        $sFill       = \urldecode($this->oParameters->sFill);
        $sFilled     = \str_repeat($sFill[$iFrameNumber % \strlen($sFill)], $iWidth) . "\n";
        $iLineCount  = $this->oDisplay->getHeight();
        $iFrameNumber >>= $this->oParameters->iRate;
        while ($iLineCount--) {
            if (0 == ($iFrameNumber++ % $this->oParameters->iSpacing)) {
                $sDrawBuffer .= $sFilled;
            } else {
                $sDrawBuffer .= $sBlank;
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
