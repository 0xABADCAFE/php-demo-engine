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
 * SimpleLine
 *
 * Lame test effect.
 */
class SimpleLine implements PDE\IRoutine {

    use TRoutine;

    const DEFAULT_PARAMETERS = [
        'iSpacing' => 3,
        'iRate'    => 4,
        'sFill'    => '_'
    ];

    private string $sBlank  = '';
    private string $sFilled = '';

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->oDisplay = $oDisplay;
        $iWidth = $oDisplay->getWidth();
        $this->sBlank  = str_repeat(' ', $iWidth) . "\n";
        $this->sFilled = str_repeat($this->oParameters->sFill, $iWidth) . "\n";
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        $sDrawBuffer = &$this->oDisplay->getRaw();
        $sDrawBuffer = '';
        $iLineCount = $this->oDisplay->getHeight();
        $iFrameNumber >>= $this->oParameters->iRate;
        while ($iLineCount--) {
            if (0 == ($iFrameNumber++ % $this->oParameters->iSpacing)) {
                $sDrawBuffer .= $this->sFilled;
            } else {
                $sDrawBuffer .= $this->sBlank;
            }
        }
        return $this;
    }
}
