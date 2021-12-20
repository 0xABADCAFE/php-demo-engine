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
 * Toroid
 *
 * Mmmm doughnuts.
 *
 */
class Toroid extends Base {

    const
        TWICE_PI       = 2 * M_PI,
        EIGHTH_PI      = 0.125 * M_PI
    ;

    const
        DRAW_ASCII_GREY         = 1,
        DRAW_ASCII_GREY_DARK_BG = 2,
        DRAW_ASCII_RGB          = 4,
        DRAW_ASCII_RGB_DARK_BG  = 8,
        DRAW_BLOCK_GREY         = 16,
        DRAW_BLOCK_RGB          = 32,

        MASK_NEEDS_ASCII_BUFFER = 15,
        MASK_NEEDS_PIX_BUFFER   = 62,
        PALETTE_SIZE            = 256
    ;

    const PLOT_FUNCTIONS = [
        self::DRAW_ASCII_GREY         => 'plotASCIIGrey',
        self::DRAW_ASCII_GREY_DARK_BG => 'plotASCIIGreyDarkenBG',
        self::DRAW_ASCII_RGB          => 'plotASCIIRGB',
        self::DRAW_ASCII_RGB_DARK_BG  => 'plotASCIIRGBDarkenBG',
        self::DRAW_BLOCK_GREY         => 'plotBlockGrey',
        self::DRAW_BLOCK_RGB          => 'plotBlockRGB'
    ];

    const DEFAULT_PARAMETERS = [
        'fAxis1Rotation' => 0.0,
        'fAxis2Rotation' => 0.0,
        'fAxis1Step'     => 0.04,
        'fAxis2Step'     => 0.02,
        'fPoloidStep'    => 0.04,
        'fToroidStep'    => 0.01,
        'fRenderXScale'  => 64.0,
        'fRenderYScale'  => 32.0,
        'fMinLuma'       => 0.0,
        'fLumaFactor'    => 0.666,
        'fUncoilPoloid'  => 0.0,
        'fUncoilToroid'  => 1.0,
        'iDrawMode'      => self::DRAW_ASCII_GREY
    ];


    /**
     * Runtime properties extracted from the IDisplay instance.
     */
    private int $iCentreX, $iCentreY, $iSpan, $iMaxX, $iMaxY, $iArea, $iDrawMask;


    /**
     * ASCII specific mode rendering facts
     */
    private int    $iCharMaxLuma;
    private string $sCharDrawBuffer, $sLumaCharLUT;

    private ?SPLFixedArray $oPixelBuffer = null;

    /**
     * Pixel specific mode rendering facts
     */
    private array $aPalettePoints = [
        0   => 0x000033,
        200 => 0x0000CC,
        240 => 0x0000FF,
        255 => 0xFFFFFF
    ];

    private SPLFixedArray $oPalette;

    /**
     * Basic constructor
     *
     * @implements IRoutine::__construct()
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        parent::__construct($oDisplay, $aParameters);
        $this->oPalette = (new PDE\Graphics\Palette(self::PALETTE_SIZE))->gradient($this->aPalettePoints);
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->oDisplay  = $oDisplay;
        // Dimension related
        $this->iCentreX  = $oDisplay->getWidth() >> 1;
        $this->iCentreY  = $oDisplay->getHeight() >> 1;
        $this->iMaxX     = $oDisplay->getWidth()-1;
        $this->iMaxY     = $oDisplay->getHeight()-1;
        $this->iArea     = $oDisplay->getWidth() * $oDisplay->getHeight();
        $this->iDrawMask = 0;

        // Start with the most basic
        if ($oDisplay instanceof PDE\Display\IASCIIArt) {
            $this->iDrawMask    = self::DRAW_ASCII_GREY;
            $this->iCharMaxLuma = $this->oDisplay->getMaxLuminance();
            $this->sLumaCharLUT = $this->oDisplay->getLuminanceCharacters();
            $this->iSpan        = $oDisplay->getCharacterWidth();
        }

        // Pixel type behaviour?
        if ($oDisplay instanceof PDE\Display\IPixelled) {
            $this->iDrawMask |= self::DRAW_BLOCK_GREY | self::DRAW_BLOCK_RGB;

            // Both available?
            if ($this->iDrawMask & self::DRAW_ASCII_GREY) {
                $this->iDrawMask |= self::DRAW_ASCII_GREY_DARK_BG | self::DRAW_ASCII_RGB | self::DRAW_ASCII_RGB_DARK_BG;
            }
        }

        $this->bCanRender = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        $iDrawMode = $this->iDrawMask & $this->oParameters->iDrawMode;
        if ($iDrawMode) {

            if ($iDrawMode & self::MASK_NEEDS_PIX_BUFFER) {
                $this->oPixelBuffer = $this->oDisplay->getPixels();
            }
            if ($iDrawMode & self::MASK_NEEDS_ASCII_BUFFER) {
                $this->sCharDrawBuffer = &$this->oDisplay->getCharacterBuffer();
                $this->iCharMaxLuma = $this->oDisplay->getMaxLuminance();
                $this->sLumaCharLUT = $this->oDisplay->getLuminanceCharacters();
                $this->iSpan        = $this->oDisplay->getCharacterWidth();
            }
            $cPlotPixel = [$this, self::PLOT_FUNCTIONS[$iDrawMode]];

            $fCosAxis1Rot  = cos($this->oParameters->fAxis1Rotation);
            $fSinAxis1Rot  = sin($this->oParameters->fAxis1Rotation);
            $fCosAxis2Rot  = cos($this->oParameters->fAxis2Rotation);
            $fSinAxis2Rot  = sin($this->oParameters->fAxis2Rotation);
            $aDepthBuffer  = array_fill(0, $this->iArea, 0.0);

            $fToroidStep   = $this->oParameters->fToroidStep;
            $fPoloidStep   = $this->oParameters->fPoloidStep;

            // This is to do the dissolve into rings effect
            if (abs($this->oParameters->fUncoilToroid) > 0) {
                $fToroidStep = self::EIGHTH_PI * (1.0 - cos(
                    $fTimeIndex * $this->oParameters->fUncoilToroid
                )) + $this->oParameters->fToroidStep;
            }
            // This is to do the dissolve into rings effect
            if (abs($this->oParameters->fUncoilPoloid) > 0) {
                $fPoloidStep = self::EIGHTH_PI * (1.0 - cos(
                    $fTimeIndex * $this->oParameters->fUncoilPoloid
                )) + $this->oParameters->fPoloidStep;
            }

            $iWidth = $this->iMaxX + 1;
            for ($fPoloid = 0.0; $fPoloid < self::TWICE_PI; $fPoloid += $fPoloidStep) {
                $fCosPoloid = cos($fPoloid);
                $fSinPoloid = sin($fPoloid);
                for ($fToroid = 0.0; $fToroid < self::TWICE_PI; $fToroid += $fToroidStep) {
                    $fSinToroid = sin($fToroid);
                    $fCosToroid = cos($fToroid);
                    $fTemp1     = $fCosPoloid + 2.0;
                    $fDepth     = 1.0 / ($fSinToroid * $fTemp1 * $fSinAxis1Rot + $fSinPoloid * $fCosAxis1Rot + 5.0);
                    $fTemp2     = $fSinToroid * $fTemp1 * $fCosAxis1Rot - $fSinPoloid * $fSinAxis1Rot;

                    // Screen coordinate calculation
                    $iXPos = $this->iCentreX + (int)(
                        $this->oParameters->fRenderXScale * $fDepth * (
                            $fCosToroid * $fTemp1 * $fCosAxis2Rot - $fTemp2 * $fSinAxis2Rot
                        )
                    );

                    // Clip X
                    if ($iXPos < 0 || $iXPos > $this->iMaxX) {
                        continue;
                    }

                    $iYPos = $this->iCentreY + (int)(
                        $this->oParameters->fRenderYScale * $fDepth * (
                            $fCosToroid * $fTemp1 * $fSinAxis2Rot + $fTemp2 * $fCosAxis2Rot
                        )
                    );

                    // Clip Y
                    if ($iYPos < 0 || $iYPos > $this->iMaxY) {
                        continue;
                    }

                    $iBufferPos = $iXPos + $iWidth * $iYPos;

                    // If the depth test passes, calculate the luminance of this pixel
                    if (
                        $fDepth > $aDepthBuffer[$iBufferPos]
                    ) {
                        $aDepthBuffer[$iBufferPos] = $fDepth;
                        $cPlotPixel($iBufferPos, $iXPos, $iYPos, $this->oParameters->fLumaFactor * max(
                            ($fSinPoloid * $fSinAxis1Rot - $fSinToroid * $fCosPoloid * $fCosAxis1Rot) * $fCosAxis2Rot -
                            $fSinToroid * $fCosPoloid * $fSinAxis1Rot -
                            $fSinPoloid * $fCosAxis1Rot -
                            $fCosToroid * $fCosPoloid * $fSinAxis2Rot,
                            0
                        ));
                    }
                }
            }
            $this->oParameters->fAxis1Rotation += $this->oParameters->fAxis1Step;
            $this->oParameters->fAxis2Rotation += $this->oParameters->fAxis2Step;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {

    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotASCIIGrey(int $iBufferPos, int $iXPos, int $iYPos, float $fLuma) {
        $iLuminance = (int)min((
            $this->oParameters->fMinLuma +
            $this->oParameters->fLumaFactor * $fLuma * $this->iCharMaxLuma
        ), $this->iCharMaxLuma);
        $this->sCharDrawBuffer[$iXPos + $this->iSpan * $iYPos] = $this->sLumaCharLUT[$iLuminance];
    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotASCIIGreyDarkenBG(int $iBufferPos, int $iXPos, int $iYPos, float $fLuma) {
        $this->plotASCIIGrey($iBufferPos, $iXPos, $iYPos, $fLuma);
        $iHalfRGB  = ($this->oPixelBuffer[$iBufferPos] >> 1) & 0x007F7F7F;
        $iQtrRGB   = ($iHalfRGB >> 1) & 0x007F7F7F;
        $this->oPixelBuffer[$iBufferPos] = $iHalfRGB + $iQtrRGB;
    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotASCIIRGB(int $iBufferPos, int $iXPos, int $iYPos, float $fLuma) {

    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotASCIIRGBDarkenBG(int $iBufferPos, int $iXPos, int $iYPos, float $fLuma) {

    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotBlockGrey(int $iBufferPos, $iXPos, int $iYPos, float $fLuma) {
        $iLuminance = (int)min((
            $this->oParameters->fMinLuma +
            $this->oParameters->fLumaFactor * $fLuma * 255
        ), 255);
        $this->oPixelBuffer[$iBufferPos] = $iLuminance << 16 | $iLuminance << 8 | $iLuminance;
    }

    /**
     * @param int $iBufferPos
     * @param int $iXPos
     * @param int $iYPos
     * @param float $fLuma
     */
    private function plotBlockRGB(int $iBufferPos, int $iXPos, int $iYPos, float $fLuma) {
        $iPaletteIndex = (int)min((
            $this->oParameters->fMinLuma +
            $this->oParameters->fLumaFactor * $fLuma * self::PALETTE_SIZE
        ), self::PALETTE_SIZE - 1);

        $this->oPixelBuffer[$iBufferPos] = $this->oPalette[$iPaletteIndex];
    }


}
