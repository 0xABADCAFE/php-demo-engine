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
 * Display an image
 *
 * TODO controls and optimise
 */
class RGBImage extends Base implements IResourceLoader {

    use TResourceLoader;

    const DEFAULT_PARAMETERS = [
        'sPath' => 'required'
    ];

    public function preload() : self {
        $this->loadPNM($this->oParameters->sPath);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay   = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {
    }

    /**
     * Load a PNM image
     */
    protected function loadPNM(string $sPath) {
        $sRaw = $this->loadFile($sPath);
        if (preg_match('/^(\d+)\s+(\d+)$/m', $sRaw, $aMatches)) {
            $iWidth  = (int)$aMatches[1];
            $iHeight = (int)$aMatches[2];
            $sData   = substr($sRaw, ($iWidth * $iHeight * -3));
            printf("%d x %d, %d\n", $iWidth, $iHeight, strlen($sData));
            exit();
        }
    }

}
