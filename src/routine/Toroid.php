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
 *                             P(?:ointless|ortable|HP) Demo Engine/
 */

declare(strict_types=1);

namespace ABadCafe\PDE\Routine;

use ABadCafe\PDE;

/**
 * Toroid
 *
 * Mmmm doughnuts/
 *
 * @todo - switch out constants to a parameter list that can be changed.
 */
class Toroid implements PDE\IRoutine {

    const
        AXIS_1_STEP    = 0.04,
        AXIS_2_STEP    = 0.02,
        POLOIDAL_STEP  = 0.05,
        TOROIDAL_STEP  = 0.01,
        RENDER_X_SCALE = 50.0,
        RENDER_Y_SCALE = 25.0,
        TWICE_PI       = 2 * M_PI,
        EIGHTH_PI      = 0.125 * M_PI,
        LUMINANCE_FAC  = 0.666
    ;

    private float
        $fAxis2Rotation = 0.0,
        $fAxis1Rotation = 0.0
    ;


    /**
     * @inheritDoc
     */
    public function getPriotity() : int {
        return 0;
    }

    /**
     * @inheritDoc
     */
    public function render(PDE\IDisplay $oDisplay, int $iFrameNumber, float $fTimeIndex) : self {
        $iCenterX      = $oDisplay->getWidth() >> 1;
        $iCenterY      = $oDisplay->getHeight() >> 1;
        $fSinAxis1Rot  = sin($this->fAxis1Rotation);
        $fCosAxis1Rot  = cos($this->fAxis1Rotation);
        $fCosAxis2Rot  = cos($this->fAxis2Rotation);
        $fSinAxis2Rot  = sin($this->fAxis2Rotation);
        $aDepthBuffer  = array_fill(0, $oDisplay->getWidth()*$oDisplay->getHeight(), 0.0);
        $sDrawBuffer   = &$oDisplay->getRaw();

        // This is to do the dissolve into rings effect
        $fToroidalStep = self::EIGHTH_PI * (1.0 - cos($fTimeIndex)) + self::TOROIDAL_STEP;

        $iSpan = $oDisplay->getSpanWidth();

        // Get the raw luminance properties
        $sLuma = $oDisplay->getRawLuma();
        $fLuma = self::LUMINANCE_FAC * $oDisplay->getMaxRawLuma();

        for ($fPoloid = 0.0; $fPoloid < self::TWICE_PI; $fPoloid += self::POLOIDAL_STEP) {
            $fCosPoloid = cos($fPoloid);
            $fSinPoloid = sin($fPoloid);
            for ($fToroid = 0.0; $fToroid < self::TWICE_PI; $fToroid += $fToroidalStep) {
                $fSinToroid = sin($fToroid);
                $fCosToroid = cos($fToroid);
                $fTemp1     = $fCosPoloid + 2.0;
                $fDepth     = 1.0 / ($fSinToroid * $fTemp1 * $fSinAxis1Rot + $fSinPoloid * $fCosAxis1Rot + 5.0);
                $fTemp2     = $fSinToroid * $fTemp1 * $fCosAxis1Rot - $fSinPoloid * $fSinAxis1Rot;
                $iXPos      = $iCenterX + (int)(self::RENDER_X_SCALE * $fDepth * ($fCosToroid * $fTemp1 * $fCosAxis2Rot - $fTemp2 * $fSinAxis2Rot));
                $iYPos      = $iCenterY + (int)(self::RENDER_Y_SCALE * $fDepth * ($fCosToroid * $fTemp1 * $fSinAxis2Rot + $fTemp2 * $fCosAxis2Rot));
                $iBufferPos = $iXPos + $iSpan * $iYPos;

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
                    $sDrawBuffer[$iBufferPos] = $sLuma[$iLuminance];
                }
            }
        }
        $this->fAxis1Rotation += self::AXIS_1_STEP;
        $this->fAxis2Rotation += self::AXIS_2_STEP;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setParameters(array $aParams) : self {
        return $this;
    }
}
