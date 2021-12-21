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

use function \sin, \min, \sqrt, \atan2;

/**
 * Display an image
 */
class Tunnel extends Base implements IResourceLoader {

    use TResourceLoader;

    // Basic texture
    private Graphics\Image $oTexture;

    // Calculation tables

    /** @var SPLFixedArray<int> */
    private SPLFixedArray $oDistanceTable, $oAngleTable;

    const DEFAULT_PARAMETERS = [
        'sTexPath'     => 'required', // The path to the texture
        'fLenRatio'    => 8.0,        // Distance tiling factor
        'fCirRatio'    => 1.0,        // Circumference tiling factor
        'iTexDim'      => 8,          // Texture dimension (power of 2) - image must match this
        'fHPanRate'    => 0.2,        // Rate at which the horizontal pan moves
        'fVPanRate'    => 0.4,        // Rate at which the vertical pan moves
        'fHPanLimit'   => 0.75,       // The maximum extent the horizontal pan moves, -1.0 ... 1.0
        'fVPanLimit'   => 0.75,       // The maximum extent the vertical pan moves, -1.0 ... 1.0
        'fLimitSqrt'   => 1.0,
        'fDepthFactor' => 0.0
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
        $iExpect = 1 << $this->oParameters->iTexDim;
        $oTexture = $this->loadPNM($this->oParameters->sTexPath);
        if (
            $oTexture->getWidth() != $iExpect ||
            $oTexture->getHeight() != $iExpect
        ) {
            throw new \Exception();
        }
        $this->oTexture = $oTexture;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        if ($this->bCanRender = ($oDisplay instanceof PDE\Display\IPixelled)) {
            $this->initTables($oDisplay);
        }
        $this->oDisplay = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        $iWidth      = $this->oDisplay->getWidth();
        $iHeight     = $this->oDisplay->getHeight();
        $iTexShift   = $this->oParameters->iTexDim;
        $iTextureDim = 1 << $this->oParameters->iTexDim;
        $iTextureMod = $iTextureDim - 1;
        $oPixels     = $this->castDisplayPixelled()->getPixels();
        $oTexels     = $this->oTexture->getPixels();
        $iIndex      = 0;

        // Calculate the panning displacement
        $iPanX       = ($iWidth  + $this->oParameters->fHPanLimit * $iWidth  * sin($fTimeIndex * $this->oParameters->fHPanRate)) >> 1;
        $iPanY       = ($iHeight + $this->oParameters->fVPanLimit * $iHeight * sin($fTimeIndex * $this->oParameters->fVPanRate)) >> 1;
        $iWidth2     = $iWidth << 1;

        for ($iY = 0; $iY < $iHeight; ++$iY) {
            $iTableY = $iY + $iPanY;
            for ($iX = 0; $iX < $iWidth; ++$iX) {
                // Get the index into our lookup table pair. This is the screen position mapped to the 2x larger
                // buffer.
                $iTableIndex = ($iTableY * $iWidth2) + $iX + $iPanX;

                // Get the distance, we will use this in a moment.
                $iDistance = $this->oDistanceTable[$iTableIndex];

                // Texture Coordinate
                $iTexX              = (($iDistance + $iFrameNumber) & $iTextureMod);
                $iTexY              = (($this->oAngleTable[$iTableIndex] + $iFrameNumber)    & $iTextureMod);
                $iTexIndex          = $iTexX + ($iTexY << $iTexShift);

                if ($this->oParameters->fDepthFactor > 0.0) {
                    $fDepthFactor       = $this->oParameters->fDepthFactor / ++$iDistance;
                    $iRGB               = $oTexels[$iTexIndex];
                    $iRed   = 0xFF & min(($iRGB >> 16)         * $fDepthFactor, 255);
                    $iGreen = 0xFF & min((($iRGB >> 8) & 0xFF) * $fDepthFactor, 255);
                    $iBlue  = 0xFF & min(($iRGB & 0xFF)        * $fDepthFactor, 255);
                    $oPixels[$iIndex++] = $iRed << 16 | $iGreen << 8 | $iBlue;
                } else {
                    $oPixels[$iIndex++] = $oTexels[$iTexIndex];
                }
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {
        $this->initTables($this->oDisplay);
    }

    /**
     * Initialise the required lookup tables for transformation
     */
    private function initTables(PDE\IDisplay $oDisplay): void {
        $iWidth         = $oDisplay->getWidth();
        $iHeight        = $oDisplay->getHeight();
        $iSize          = $iWidth * $iHeight * 4;
        $oDistanceTable = new SPLFixedArray($iSize);
        $oAngleTable    = new SPLFixedArray($iSize);
        $iTextureDim    = 1 << $this->oParameters->iTexDim;
        $iTextureMod    = $iTextureDim - 1;
        $fRatio         = $this->oParameters->fLenRatio;
        $fAngleScale    = $this->oParameters->fCirRatio * ($iTextureDim >> 1) / M_PI;
        $iIndex         = 0;
        for ($iY = 0; $iY < $iHeight * 2; ++$iY) {
            for ($iX = 0; $iX < $iWidth * 2; ++$iX) {
                $iDeltaX = $iX - $iWidth;
                $iDeltaY = $iY - $iHeight;
                $oDistanceTable[$iIndex] = $iTextureMod & (int)($fRatio * $iTextureDim / sqrt(
                    ($iDeltaX * $iDeltaX) + ($iDeltaY * $iDeltaY) + $this->oParameters->fLimitSqrt
                ));
                $oAngleTable[$iIndex] = (int)($fAngleScale * atan2($iDeltaY, $iDeltaX));
                ++$iIndex;
            }
        }
        $this->oDistanceTable = $oDistanceTable;
        $this->oAngleTable    = $oAngleTable;
    }
}
