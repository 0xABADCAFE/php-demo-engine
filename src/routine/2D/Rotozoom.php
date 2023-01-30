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
use ABadCafe\PDE\Graphics;
use \SPLFixedArray;
use function \sin, \cos, abs;

/**
 * Display an image
 */
class Rotozoom extends Base implements IResourceLoader {

    use TResourceLoader;

    private Graphics\Image $oImage;

    private float $fAngle = 0;

    const ANGLE_STEP = M_PI / 100.0;

    const DEFAULT_PARAMETERS = [
        'sPath'   => 'required',
        'fStep'   => self::ANGLE_STEP,
        'fZoom'   => 1.0,
        'fDist'   => 1.0,
        'fOfsU'   => 0.0,
        'fOfsV'   => 0.0
    ];


    /**
     * @inheritDoc
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        parent::__construct($oDisplay, $aParameters);
    }

    /**
     * @inheritDoc
     */
    public function preload(): self {
        $this->oImage = $this->loadPNM($this->oParameters->sPath);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        $this->oDisplay = $oDisplay;
        if ($oDisplay instanceof PDE\Display\IPixelled) {
            $this->bCanRender = true;
        } else {
            $this->bCanRender = false;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        $fCos = cos($this->fAngle);
        $fSin = sin($this->fAngle);
        $this->fAngle += $this->oParameters->fStep;

        $iWidth  = $this->oDisplay->getWidth();
        $iHeight = $this->oDisplay->getHeight();
        $oPixels = $this->castDisplayPixelled()->getPixels();

        $oTexels    = $this->oImage->getPixels();
        $iImgWidth  = $this->oImage->getWidth();
        $iImgHeight = $this->oImage->getHeight();

        $iMaskRGB   = 0x3F3F3F3F;
        $iDstIndex  = 0;
        $fScale     = $fSin * $this->oParameters->fZoom + $this->oParameters->fDist;
        $fOfsU      = $this->oParameters->fOfsU;
        $fOfsV      = $this->oParameters->fOfsV;

        for ($iYPos = 0; $iYPos < $iHeight; ++$iYPos) {
            for ($iXPos = 0; $iXPos < $iWidth; ++$iXPos) {

                // Do a spot of anti aliasing
                $iTexU = abs((int)(($iXPos * $fCos - $iYPos * $fSin) * $fScale + $fOfsU) % $iImgWidth);
                $iTexV = abs((int)(($iXPos * $fSin + $iYPos * $fCos) * $fScale + $fOfsV) % $iImgHeight);
                $iTexIndex0 = $iTexU + $iTexV * $iImgWidth;

                $fXPos = $iXPos + 0.5;
                $iTexU = abs((int)(($fXPos * $fCos - $iYPos * $fSin) * $fScale + $fOfsU) % $iImgWidth);
                $iTexV = abs((int)(($fXPos * $fSin + $iYPos * $fCos) * $fScale + $fOfsV) % $iImgHeight);
                $iTexIndex1 = $iTexU + $iTexV * $iImgWidth;

                $fYPos = $iYPos + 0.5;
                $iTexU = abs((int)(($iXPos * $fCos - $fYPos * $fSin) * $fScale + $fOfsU) % $iImgWidth);
                $iTexV = abs((int)(($iXPos * $fSin + $fYPos * $fCos) * $fScale + $fOfsV) % $iImgHeight);
                $iTexIndex2 = $iTexU + $iTexV * $iImgWidth;

                $iTexU = abs((int)(($fXPos * $fCos - $fYPos * $fSin) * $fScale + $fOfsU) % $iImgWidth);
                $iTexV = abs((int)(($fXPos * $fSin + $fYPos * $fCos) * $fScale + $fOfsV) % $iImgHeight);
                $iTexIndex3 = $iTexU + $iTexV * $iImgWidth;

                $oPixels[$iDstIndex++] =
                    (($oTexels[$iTexIndex0] >> 2) & $iMaskRGB) +
                    (($oTexels[$iTexIndex1] >> 2) & $iMaskRGB) +
                    (($oTexels[$iTexIndex2] >> 2) & $iMaskRGB) +
                    (($oTexels[$iTexIndex3] >> 2) & $iMaskRGB);
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
