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
        'iMode'         => self::MODE_RECORD,
        'iWidth'        => 100,
        'iHeight'       => 100,
        'sAmbientRGB'   => 'FFFFFF', // Ambient light colour
        'fAmbientLevel' => 0.05098,  // Ambient light level
        'sSkyRGB'       => '7F99FF',
        'sFloorRGB1'    => 'FF5555',
        'sFloorRGB2'    => 'FFFFFF',
        'iMaxRays'      => 8,
        'fBrightness'   => 1.098,

        'aSpheres'      => [
            [
               'fX' => 8.0,
               'fY' => 5.0,
               'fZ' => 4.0,
               'fR' => 8.0
            ],
            [
               'fX' => 15.0,
               'fY' => 5.0,
               'fZ' => 4.0,
               'fR' => 8.0
            ]
        ]
    ];

    /** @var PDE\Graphics\Image[] $aFrames */
    private array $aFrames = [];

    private Graphics\Blitter $oBlitter;

    private array $aObjects = [];
    private Vec3F
        $vCameraForward,
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

    private float $fInvRM;

    /**
     * Basic constructor
     *
     * @implements IRoutine::__construct()
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        parent::__construct($oDisplay, $aParameters);
        $i = self::MAX_FRAMES;
        while ($i--) {
            $this->aFrames[] = new PDE\Graphics\Image($this->oParameters->iWidth, $this->oParameters->iHeight);
        }

        $this->vCameraForward = new Vec3F(-6.0, -16.0, 0.0);
        $this->vFocalPoint    = new Vec3F(17.0, 16.0, 8.0);
        $this->vNormalUp      = new Vec3F(0.0, 0.0, 1.0);
        $this->vLight         = new Vec3F(9.9, 11.0, 20.0);
        $this->vAmbientRGB    = new Vec3F(13.0, 13.0, 13.0);
        $this->vFloorRGB1     = new Vec3F(3.0, 1.0, 1.0);
        $this->vFloorRGB2     = new Vec3F(3.0, 3.0, 3.0);
        $this->vBlack         = new Vec3F(0.0, 0.0, 0.0);
        $this->vSkyRGB        = new Vec3F(0.5, 0.6, 1.0);
        $this->aObjects[]     = new Vec3F(8.0, 5.0, 4.0);
        $this->aObjects[]     = new Vec3F(15.0, 5.0, 4.0);

        $this->fInvRM = 0.25 / (float)mt_getrandmax();

        $this->oBlitter = new Graphics\Blitter();
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay) : self {
        $this->bCanRender  = ($oDisplay instanceof PDE\Display\IPixelled);
        $this->oDisplay    = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        $this->renderScene();
        $this->oBlitter
            ->setSource($this->aFrames[0])
            ->setTarget($this->oDisplay)
            ->copy(
                0,
                0,
                0,
                0,
                $this->aFrames[0]->getWidth(),
                $this->aFrames[0]->getHeight()
            );
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {

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
    private function trace(Vec3F $vOrigin, Vec3F $vDirection, ?float &$fTraceDistance, ?Vec3F &$vNormal) : int {

        $fTraceDistance = 100;

        // Assume trace hits nothing
        $iMaterial = self::MAT_SKY;
        $fZLevel   = -$vOrigin->fZ / $vDirection->fZ;

        // Check if trace maybe hits floor
        if (0.01 < $fZLevel) {
            $fTraceDistance = $fZLevel;
            $vNormal   = $this->vNormalUp;
            $iMaterial = self::MAT_FLOOR;
        }

        foreach ($this->aObjects as $vObject) {
            $vPoint    = $vOrigin->iSub($vObject);
            $fDot      = $vPoint->dot($vDirection);
            $fEye      = $vPoint->dot($vPoint) - 8.0;
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
    function sample(Vec3F $vOrigin, Vec3F $vDirection) : Vec3F {

        if (++$this->iRecursion > 3) {
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
            $vIntersect->scale(0.2);
            --$this->iRecursion;
            return (
                // Compute check colour based on the position
                (int) (ceil($vIntersect->fX * 0.5) + ceil($vIntersect->fY * 0.5)) & 1 ?
                $this->vFloorRGB1 :
                $this->vFloorRGB2   // white
            )->iScale($fLambertian * 0.2 + 0.1);

        }

        // Hit a sphere? Bounce it
        $vRGB = $this->sample($vIntersect, $vHalfVector)->iScale(0.75);

        if ($fLambertian > 0) {
            // Compute the specular highlight power
            $fSpecular = pow($vLight->dot($vHalfVector), 99.0);
            $vRGB->fX += $fSpecular;
            $vRGB->fY += $fSpecular;
            $vRGB->fZ += $fSpecular;
        }

        --$this->iRecursion;
        return $vRGB;
    }

    /**
     * Render the scene
     */
    private function renderScene() {
        $mark = microtime(true);
        $iWidth  = 100;
        $iHeight = 100;

        $fImageScale = 1.6 / $iWidth;

        // camera direction vectors
        $vCameraForward = $this->vCameraForward->iNormalise();
        $vCameraUp = $this->vNormalUp
            ->iCross($vCameraForward)
            ->normalise()
            ->scale($fImageScale);

        $vCameraRight = $vCameraForward
            ->iCross($vCameraUp)
            ->normalise()
            ->scale($fImageScale);

        $vEyeOffset = $vCameraUp
            ->iAdd($vCameraRight)
            ->scale(-0.5 * $iWidth)
            ->add($vCameraForward);

        $fStep   = M_PI / 32.0;
        $fTime   = 0.0;

        $this->aObjects[0]->fZ = 4 + 8.0 * abs(cos($fTime));
        $this->aObjects[1]->fZ = 4 + 8.0 * abs(cos($fTime + M_PI / 4.0));
        $fTime += $fStep;

        $oPixels = $this->aFrames[0]->getPixels();
        $iPixel  = 0;
        for ($iPixelY = $iHeight; $iPixelY--;) {
            for ($iPixelX = $iWidth; $iPixelX--;) {

                // Use a vector for the pixel. The values here are in the range 0.0 - 255.0 rather than the 0.0 - 1.0
                $vPixel = $this->vBlack->clone();
                $iRays = 8;
                while ($iRays--) {

                    // Random delta to be added for depth of field effects
                    $vDelta = $vCameraUp
                        ->iScale((($this->fInvRM * mt_rand()) - 0.5) * 20.0)
                        ->add(
                            $vCameraRight
                                ->iScale((($this->fInvRM * mt_rand()) - 0.5) * 20.0)
                        );


                    // Accumulate the sample result into the current pixel
                    $vPixel->add(
                        $this->sample(
                            $this->vFocalPoint->iAdd($vDelta),
                            $vCameraUp
                                ->iScale(($this->fInvRM * mt_rand()) + $iPixelX)
                                ->add(
                                    $vCameraRight
                                        ->iScale(($this->fInvRM * mt_rand()) + $iPixelY)
                                        ->add($vEyeOffset)
                                )
                                ->scale(16.0)
                                ->sub($vDelta)
                                ->normalise()
                        )
                    );
                }
                $vPixel
                    ->scale(35.0)
                    ->add($this->vAmbientRGB);

                // Convert to integers and push out to ppm outpu stream
                $oPixels[$iPixel++] =
                    min($vPixel->fX, 255) << 16 |
                    min($vPixel->fY, 255) << 8  |
                    (int)min($vPixel->fZ, 255);
            }
        }
    }
}
