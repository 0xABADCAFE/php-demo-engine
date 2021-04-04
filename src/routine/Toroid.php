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
 * Toroid
 *
 * Mmmm doughnuts.
 *
 */
class Toroid implements PDE\IRoutine {

    use TRoutine;

    const
        TWICE_PI       = 2 * M_PI,
        EIGHTH_PI      = 0.125 * M_PI
    ;

    const DEFAULT_PARAMETERS = [
        'fAxis1Rotation' => 0.0,
        'fAxis2Rotation' => 0.0,
        'fAxis1Step'     => 0.04,
        'fAxis2Step'     => 0.02,
        'fPoloidStep'    => 0.05,
        'fToroidStep'    => 0.01,
        'fRenderXScale'  => 64.0,
        'fRenderYScale'  => 32.0,
        'fLumaFactor'    => 0.666
    ];


    /**
     * Runtime properties extracted from the IDisplay instance.
     */
    private int    $iCentreX, $iCentreY, $iSpan, $iArea, $iMaxLuma;
    private string $sLumaLUT;

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->oDisplay = $oDisplay;
        // Dimension related
        $this->iCenterX = $oDisplay->getWidth() >> 1;
        $this->iCenterY = $oDisplay->getHeight() >> 1;
        $this->iSpan    = $oDisplay->getSpanWidth();
        $this->iArea    = $oDisplay->getWidth() * $oDisplay->getHeight();

        // Brightness releated
        $this->iMaxLuma = $oDisplay->getMaxRawLuma();
        $this->sLumaLUT = $oDisplay->getRawLuma();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        $fSinAxis1Rot  = sin($this->oParameters->fAxis1Rotation);
        $fCosAxis1Rot  = cos($this->oParameters->fAxis1Rotation);
        $fCosAxis2Rot  = cos($this->oParameters->fAxis2Rotation);
        $fSinAxis2Rot  = sin($this->oParameters->fAxis2Rotation);
        $aDepthBuffer  = array_fill(0, $this->iArea, 0.0);
        $sDrawBuffer   = &$this->oDisplay->getRaw();

        // This is to do the dissolve into rings effect
        $fToroidStep = self::EIGHTH_PI * (1.0 - cos($fTimeIndex)) + $this->oParameters->fToroidStep;
        $fLuma = $this->oParameters->fLumaFactor * $this->iMaxLuma;

        for ($fPoloid = 0.0; $fPoloid < self::TWICE_PI; $fPoloid += $this->oParameters->fPoloidStep) {
            $fCosPoloid = cos($fPoloid);
            $fSinPoloid = sin($fPoloid);
            for ($fToroid = 0.0; $fToroid < self::TWICE_PI; $fToroid += $fToroidStep) {
                $fSinToroid = sin($fToroid);
                $fCosToroid = cos($fToroid);
                $fTemp1     = $fCosPoloid + 2.0;
                $fDepth     = 1.0 / ($fSinToroid * $fTemp1 * $fSinAxis1Rot + $fSinPoloid * $fCosAxis1Rot + 5.0);
                $fTemp2     = $fSinToroid * $fTemp1 * $fCosAxis1Rot - $fSinPoloid * $fSinAxis1Rot;
                $iXPos      = $this->iCenterX + (int)(
                    $this->oParameters->fRenderXScale * $fDepth * ($fCosToroid * $fTemp1 * $fCosAxis2Rot - $fTemp2 * $fSinAxis2Rot)
                );
                $iYPos      = $this->iCenterY + (int)(
                    $this->oParameters->fRenderYScale * $fDepth * ($fCosToroid * $fTemp1 * $fSinAxis2Rot + $fTemp2 * $fCosAxis2Rot)
                );
                $iBufferPos = $iXPos + $this->iSpan * $iYPos;

                // If the depth test passes, calculate the luminance of this pixel
                if (
                    $fDepth > $aDepthBuffer[$iBufferPos]
                ) {
                    $aDepthBuffer[$iBufferPos] = $fDepth;
                    $iLuminance = 1 + (int)(
                        $fLuma * max(
                            ($fSinPoloid * $fSinAxis1Rot - $fSinToroid * $fCosPoloid * $fCosAxis1Rot) * $fCosAxis2Rot -
                            $fSinToroid * $fCosPoloid * $fSinAxis1Rot -
                            $fSinPoloid * $fCosAxis1Rot -
                            $fCosToroid * $fCosPoloid * $fSinAxis2Rot,
                            0
                        )
                    );
                    $sDrawBuffer[$iBufferPos] = $this->sLumaLUT[$iLuminance];
                }
            }
        }
        $this->oParameters->fAxis1Rotation += $this->oParameters->fAxis1Step;
        $this->oParameters->fAxis2Rotation += $this->oParameters->fAxis2Step;
        return $this;
    }
}
