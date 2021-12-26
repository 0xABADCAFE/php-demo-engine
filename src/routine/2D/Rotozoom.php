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

    const DEFAULT_PARAMETERS = [
        'sPath'   => 'required',
    ];

    const ANGLE_STEP = M_PI / 200.0;

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
        $this->fAngle += self::ANGLE_STEP;

        $iWidth  = $this->oDisplay->getWidth();
        $iHeight = $this->oDisplay->getHeight();
        $oPixels = $this->castDisplayPixelled()->getPixels();

        $oTexels    = $this->oImage->getPixels();
        $iImgWidth  = $this->oImage->getWidth();
        $iImgHeight = $this->oImage->getHeight();

        $iDstIndex  = 0;
        $fScale     = $fSin + 1.0;
        for ($iYPos = 0; $iYPos < $iHeight; ++$iYPos) {
            for ($iXPos = 0; $iXPos < $iWidth; ++$iXPos) {
                $iTexU = (int)abs((($iXPos * $fCos - $iYPos * $fSin) * $fScale) % $iImgWidth);
                $iTexV = (int)abs((($iXPos * $fSin + $iYPos * $fCos) * $fScale) % $iImgHeight);

                $iTexIndex = $iTexU + $iTexV * $iImgWidth;
                $oPixels[$iDstIndex++] = $oTexels[$iTexIndex];
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
