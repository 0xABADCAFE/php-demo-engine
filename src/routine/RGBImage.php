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
 * Display an image
 *
 * TODO controls and optimise
 */
class RGBImage extends Base implements IResourceLoader {

    use TResourceLoader;

    private int $iWidth, $iHeight, $iViewWidth, $iViewHeight;

    private SPLFixedArray $oPixels;

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
        $this->bCanRender  = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay    = $oDisplay;
        $this->iViewWidth  = $oDisplay->getWidth();
        $this->iViewHeight = $oDisplay->getHeight();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            $oBuffer = $this->oDisplay->getPixelBuffer();
            if ($this->iWidth == $this->iViewWidth && $this->iHeight == $this->iViewHeight) {
                foreach ($oBuffer as $i => $iBufferRGB) {
                    $oBuffer[$i] = $this->oPixels[$i];
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

    /**
     * Load a PNM image
     */
    protected function loadPNM(string $sPath) {
        $sRaw = $this->loadFile($sPath);
        if (preg_match('/^(\d+)\s+(\d+)$/m', $sRaw, $aMatches)) {
            $this->iWidth  = (int)$aMatches[1];
            $this->iHeight = (int)$aMatches[2];
            $iArea         = $this->iWidth * $this->iHeight;
            $this->oPixels = new SPLFixedArray($iArea);
            $sData         = substr($sRaw, ($iArea * -3));
            $iDataOffset   = 0;
            for ($i = 0; $i < $iArea; ++$i) {
                $this->oPixels[$i] =
                    (ord($sData[$iDataOffset++]) << 16) |
                    (ord($sData[$iDataOffset++]) << 8) |
                    (ord($sData[$iDataOffset++]));
            }
        } else {
            throw new \Exception('Invalid PNM Format');
        }
    }

}
