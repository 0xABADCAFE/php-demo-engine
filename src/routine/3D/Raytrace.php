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
use ABadCafe\PDE\Util\Vec3F;
use \SPLFixedArray;

use function \mt_getrandmax, \base_convert, \abs, \cos, \mt_rand, \min, \sqrt, \ceil, \pow;

/**
 * Raytrace a simple scene
 */
class Raytrace extends Base {

    const
        MAX_FRAMES  = 32,
        MODE_RECORD = 0,
        MODE_PLAY   = 1,
        MAT_SKY     = 0,
        MAT_FLOOR   = 1,
        MAT_OBJECT  = 2
    ;

    const DEFAULT_PARAMETERS = [
        'iMode'          => self::MODE_RECORD,
        'iMaxFrames'     => self::MAX_FRAMES,
        'iWidth'         => 100,
        'iHeight'        => 100,
        'iPosX'          => 0,
        'iPosY'          => 0,
        'fImageScale'    => 1.6,
        'sAmbientRGB'    => 'FFFFFF', // Ambient light colour
        'fAmbientBright' => 0.05098,  // Ambient light level
        'sSkyRGB'        => '7F99FF',
        'fSkyBright'     => 0.005,
        'sFloorRGB1'     => 'FF5555',
        'sFloorRGB2'     => 'FFFFFF',
        'fFloorBright'   => 0.01176,
        'fFloorScale'    => 0.1,
        'fMirrorAlbedo'  => 0.75,
        'fSpecularPower' => 20.0,
        'iMaxRays'       => 8,
        'fBrightness'    => 1.098,
        'aCameraDir'     => [-6.0,-16.0, 0.0 ],
        'aFocalPoint'    => [17.0, 16.0, 8.0 ],
        'fDepthOfField'  => 20.0,
        'fScaleDOF'      => 16.0,
        'aLight'         => [9.9, 11.0, 20.0],
        'aSpheres'       => [
            [ 8.0, 5.0, 4.0, 8.0 ], // Coordinate, Radius
            [15.0, 5.0, 4.0, 8.0 ]  // Coordinate, Radius
        ],
        'aAnimation' => [
            [3.0, 8.0, 0.0],        // Base Z, Max Z, Phase
            [3.0, 8.0, 0.25]        // Base Z, Max Z, Phase
        ]
    ];

    /** @var PDE\Graphics\Image[] $aFrames */
    private array $aFrames = [];

    private Graphics\Blitter $oBlitter;

    /** @var Vec3F[] $aSpheres */
    private array $aSpheres = [];

    /** @var float[] $aRadii */
    private array $aRadii = [];

    private Vec3F
        $vCameraDirection,
        $vCameraForward,
        $vCameraUp,
        $vCameraRight,
        $vEyeOffset,
        $vFocalPoint,
        $vNormalUp,
        $vLight,
        $vAmbientRGB,
        $vFloorRGB1,
        $vFloorRGB2,
        $vBlack,
        $vSkyRGB
    ;

    private int $iRecursion = 0;

    private float
        $fInvRM,
        $fSimulationTime = 0.0
    ;

    /**
     * @inheritDoc
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        // These must be initialised no matter what
        $this->vNormalUp        = new Vec3F(0.0, 0.0, 1.0);
        $this->vBlack           = new Vec3F(0.0, 0.0, 0.0);

        parent::__construct($oDisplay, $aParameters);

        $this->oBlitter = new Graphics\Blitter();

        $this->fInvRM = 0.25 / (float)mt_getrandmax();
        $this->initCamera();
        $this->initLights();
        $this->initObjects();
        $this->initMaterials();
        $this->initBuffers();
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        $this->bCanRender  = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay    = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        $this->renderScene();
        $this->oBlitter
            ->setSource($this->aFrames[0])
            ->setTarget($this->oDisplay)
            ->copy(
                0,
                0,
                $this->oParameters->iPosX,
                $this->oParameters->iPosY,
                $this->aFrames[0]->getWidth(),
                $this->aFrames[0]->getHeight()
            );
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {
        $this->initCamera();
        $this->initLights();
        $this->initObjects();
        $this->initMaterials();
    }

    /**
     * Initialise the camera properties.
     */
    private function initCamera(): void {

        $this->vCameraDirection = new Vec3F(
            $this->oParameters->aCameraDir[0],
            $this->oParameters->aCameraDir[1],
            $this->oParameters->aCameraDir[2]
        );

        $this->vFocalPoint = new Vec3F(
            $this->oParameters->aFocalPoint[0],
            $this->oParameters->aFocalPoint[1],
            $this->oParameters->aFocalPoint[2]
        );

        $iWidth  = $this->oParameters->iWidth;

        $fImageScale = $this->oParameters->fImageScale / $iWidth;

        // camera direction vectors
        $this->vCameraForward = $this->vCameraDirection
            ->iNormalise();

        $this->vCameraUp = $this->vNormalUp
            ->iCross($this->vCameraForward)
            ->normalise()
            ->scale($fImageScale);

        $this->vCameraRight = $this->vCameraForward
            ->iCross($this->vCameraUp)
            ->normalise()
            ->scale($fImageScale);

        $this->vEyeOffset = $this->vCameraUp
            ->iAdd($this->vCameraRight)
            ->scale(-0.5 * $iWidth)
            ->add($this->vCameraForward);
    }

    /**
     * Initialise light source
     */
    private function initLights(): void {
        $this->vLight = new Vec3F(
            $this->oParameters->aLight[0],
            $this->oParameters->aLight[1],
            $this->oParameters->aLight[2]
        );
    }

    /**
     * Initialise material properties
     */
    private function initMaterials(): void {
        $this->vAmbientRGB = $this->hexRGBToVec3F($this->oParameters->sAmbientRGB)
            ->scale($this->oParameters->fAmbientBright);

        $this->vFloorRGB1 = $this->hexRGBToVec3F($this->oParameters->sFloorRGB1)
            ->scale($this->oParameters->fFloorBright);

        $this->vFloorRGB2 = $this->hexRGBToVec3F($this->oParameters->sFloorRGB2)
            ->scale($this->oParameters->fFloorBright);

        $this->vSkyRGB    = $this->hexRGBToVec3F($this->oParameters->sSkyRGB)
            ->scale($this->oParameters->fSkyBright);
    }

    private function hexRGBToVec3F(string $sColourRGB): Vec3F {
        $iRGB = (int)base_convert($sColourRGB, 16, 10);
        return new Vec3F(
            (float)($iRGB >> 16),
            (float)(($iRGB >> 8) & 0xFF),
            (float)($iRGB & 0xFF)
        );
    }

    /**
     * Initialise objects
     */
    private function initObjects(): void {
        $this->aSpheres = [];
        $this->aRadii   = [];
        foreach ($this->oParameters->aSpheres as $aSphere) {
            $this->aSpheres[] = new Vec3F(
                $aSphere[0], $aSphere[1], $aSphere[2]
            );
            $this->aRadii[] = $aSphere[3];
        }
    }

    /**
     * Initialise draw buffer
     */
    private function initBuffers(): void {
        // TODO - once asynchronous record mode is working, use a buffer per frame.
        $i = 1;//$this->oParameters->iMaxFrames;
        while ($i--) {
            $this->aFrames[] = new PDE\Graphics\Image($this->oParameters->iWidth, $this->oParameters->iHeight);
        }
    }

    /**
     * Render the scene
     */
    private function renderScene(): void {
        $iWidth      = $this->oParameters->iWidth;
        $iHeight     = $this->oParameters->iHeight;
        $fImageScale = $this->oParameters->fImageScale / $iWidth;
        $fStep       = M_PI / $this->oParameters->iMaxFrames;
        foreach ($this->aSpheres as $iIndex => $vSpherePos) {
            $aAnimation = $this->oParameters->aAnimation[$iIndex];
            $vSpherePos->fZ = $aAnimation[0] + $aAnimation[1] * abs(cos(
                $this->fSimulationTime + M_PI * $aAnimation[2]
            ));
        }

        $this->fSimulationTime += $fStep;
        $fDepthOfField = $this->oParameters->fDepthOfField;
        $fScaleDOF     = $this->oParameters->fScaleDOF;
        $iMaxRays      = $this->oParameters->iMaxRays;
        $fRGBScale     = $this->oParameters->fBrightness * 255.0 / $iMaxRays;
        $oPixels       = $this->aFrames[0]->getPixels();
        $iPixel        = 0;
        for ($iPixelY = $iHeight; $iPixelY--;) {
            for ($iPixelX = $iWidth; $iPixelX--;) {

                // Use a vector for the pixel. The values here are in the range 0.0 - 255.0 rather than the 0.0 - 1.0
                $vPixel = $this->vBlack->clone();
                $iRays  = $iMaxRays;
                while ($iRays--) {

                    // Random delta to be added for depth of field effects
                    $vDelta = $this->vCameraUp
                        ->iScale((($this->fInvRM * mt_rand()) - 0.5) * $fDepthOfField)
                        ->add(
                            $this->vCameraRight
                                ->iScale((($this->fInvRM * mt_rand()) - 0.5) * $fDepthOfField)
                        );

                    // Accumulate the sample result into the current pixel
                    $vPixel->add(
                        $this->sample(
                            $this->vFocalPoint->iAdd($vDelta),
                            $this->vCameraUp
                                ->iScale(($this->fInvRM * mt_rand()) + $iPixelX)
                                ->add(
                                    $this->vCameraRight
                                        ->iScale(($this->fInvRM * mt_rand()) + $iPixelY)
                                        ->add($this->vEyeOffset)
                                )
                                ->scale($fScaleDOF)
                                ->sub($vDelta)
                                ->normalise()
                        )
                    );
                }
                $vPixel
                    ->scale($fRGBScale)
                    ->add($this->vAmbientRGB);

                // Convert to integers and push out to ppm outpu stream
                $oPixels[$iPixel++] =
                    min($vPixel->fX, 255) << 16 |
                    min($vPixel->fY, 255) << 8  |
                    (int)min($vPixel->fZ, 255);
            }
        }
    }

    /**
     * Trace along a vector
     *
     * @param  Vec3F       $vOrigin
     * @param  Vec3F       $vDirection
     * @param  float|null &$fTraceDistance
     * @param  Vec3F|null &$vNormal
     * @return int
     */
    private function trace(Vec3F $vOrigin, Vec3F $vDirection, ?float &$fTraceDistance, ?Vec3F &$vNormal): int {

        $fTraceDistance = 1000.0;

        // Assume trace hits nothing
        $iMaterial = self::MAT_SKY;
        $fZLevel   = -$vOrigin->fZ / $vDirection->fZ;

        // Check if trace maybe hits floor
        if (0.01 < $fZLevel) {
            $fTraceDistance = $fZLevel;
            $vNormal   = $this->vNormalUp;
            $iMaterial = self::MAT_FLOOR;
        }

        foreach ($this->aSpheres as $iIndex => $vSpherePos) {
            $vPoint    = $vOrigin->iSub($vSpherePos);
            $fDot      = $vPoint->dot($vDirection);
            $fEye      = $vPoint->dot($vPoint) - $this->aRadii[$iIndex];
            $fIntrSqrd = $fDot * $fDot - $fEye;

            if ($fIntrSqrd > 0.0) {
                $fObjectDistance = -$fDot - sqrt($fIntrSqrd);
                if ($fObjectDistance < $fTraceDistance && $fObjectDistance > 0.01) {
                    $fTraceDistance = $fObjectDistance;
                    $vNormal = $vDirection
                        ->iScale($fTraceDistance)
                        ->add($vPoint);
                    $iMaterial = self::MAT_OBJECT;
                    break;
                }
            }
        }
        if ($vNormal && $vNormal !== $this->vNormalUp) {
            $vNormal->normalise();
        }
        return $iMaterial;
    }

    /**
     * Sample the light in the given direction
     *
     * @param  Vec3F $vOrigin
     * @param  Vec3F $vDirection
     * @return Vec3F (rgb)
     */
    function sample(Vec3F $vOrigin, Vec3F $vDirection): Vec3F {

        if (++$this->iRecursion > 4) {
            --$this->iRecursion;
            return $this->vBlack;
        }

        // Find where the ray intersects the world
        $iMaterial = $this->trace($vOrigin, $vDirection, $fTraceDistance, $vNormal);

        // Hit nothing? Sky shade
        if ($iMaterial === 0) {
            $fGradient = 1.0 - $vDirection->fZ;
            $fGradient *= $fGradient;
            $fGradient *= $fGradient;
            --$this->iRecursion;
            return $this->vSkyRGB->iScale($fGradient); // Blueish sky colour
        }

        $vIntersect = $vDirection
            ->iScale($fTraceDistance)
            ->add($vOrigin);

        // Calculate the lighting vector
        $vLight = $this->vLight->iSub($vIntersect);
        $vLight->fX += ($this->fInvRM * mt_rand());
        $vLight->fY += ($this->fInvRM * mt_rand());
        $vLight->normalise();

        $vHalfVector = $vNormal
            ->iScale($vNormal->dot($vDirection) * -2.0)
            ->add($vDirection);

        // Calculate the lambertian illumuination factor
        $fLambertian = $vLight->dot($vNormal);

        if ($fLambertian < 0 || $this->trace($vIntersect, $vLight, $fTraceDistance, $vNormal)) {
            $fLambertian = 0; // in shadow
        }

        // Hit the floor plane
        if ($iMaterial === 1) {
            $vIntersect->scale($this->oParameters->fFloorScale);
            --$this->iRecursion;
            return (
                // Compute check colour based on the position
                (int) (ceil($vIntersect->fX) + ceil($vIntersect->fY)) & 1 ?
                $this->vFloorRGB1 :
                $this->vFloorRGB2   // white
            )->iScale($fLambertian * 0.2 + 0.1);

        }

        // Hit a sphere? Bounce it
        $vRGB = $this->sample($vIntersect, $vHalfVector)
            ->iScale($this->oParameters->fMirrorAlbedo);

        if ($fLambertian > 0) {
            // Compute the specular highlight power
            $fSpecular = pow($vLight->dot($vHalfVector), $this->oParameters->fSpecularPower);
            $vRGB->fX += $fSpecular;
            $vRGB->fY += $fSpecular;
            $vRGB->fZ += $fSpecular;
        }

        --$this->iRecursion;
        return $vRGB;
    }
}
