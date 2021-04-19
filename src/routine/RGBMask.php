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
 * Changes the write mask
 *
 */
class RGBMask extends Base {

    const DEFAULT_PARAMETERS = [
        'sMask' => "FFFFFF"
    ];

    private int $iMask = 0xFFFFFF;

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->bCanRender   = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay     = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            $this->oDisplay->setRGBWriteMask($this->iMask);
        }
        return $this;
    }

    public function disable(int $iFrameNumber, float $fTimeIndex) : self {
        parent::disable($iFrameNumber, $fTimeIndex);
        if ($this->bCanRender) {
            $this->oDisplay->setRGBWriteMask(0xFFFFFF);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {
        $this->iMask = (int)base_convert($this->oParameters->sMask, 16, 10);
    }
}
