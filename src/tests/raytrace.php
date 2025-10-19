<?php


declare(strict_types = 1);

require_once '../PDE.php';
use ABadCafe\PDE;
use ABadCafe\PDE\Util\Vec3F;

error_reporting(-1);


///////////////////////////////////////////////////////////////////////////////

$invRM = 0.25 / (float)mt_getrandmax();

// Get a random number in the range 0.0 - 1.0
function frand() : float {
    global $invRM;
    return $invRM * mt_rand();
}

///////////////////////////////////////////////////////////////////////////////

class Scene {

    public static array $aObjects = [];
    public static Vec3F
        $oFocalPoint,
        $oNormalUp,
        $oLight,
        $oAmbientRGB,
        $oFloorRGB1,
        $oFloorRGB2,
        $oBlack,
        $oSkyRGB
    ;

    public static function init() {
        self::$oFocalPoint = new Vec3F(17.0, 16.0, 8.0);
        self::$oNormalUp   = new Vec3F(0.0, 0.0, 1.0);
        self::$oLight      = new Vec3F(9.9, 11.0, 20.0);
        self::$oAmbientRGB = new Vec3F(13.0, 13.0, 13.0);
        self::$oFloorRGB1  = new Vec3F(3.0, 1.0, 1.0);
        self::$oFloorRGB2  = new Vec3F(3.0, 3.0, 3.0);
        self::$oBlack      = new Vec3F(0.0, 0.0, 0.0);
        self::$oSkyRGB     = new Vec3F(0.5, 0.6, 1.0);
        self::$aObjects[]  = new Vec3F(8.0, 5.0, 4.0);
        self::$aObjects[]  = new Vec3F(15.0, 5.0, 4.0);
    }
};


// Trace
function trace(Vec3F $oOrigin, Vec3F $oDirection, ?float &$fTraceDistance, ?Vec3F &$oNormal) : int {

    $fTraceDistance = 100;

    // Assume trace hits nothing
    $iMaterial = 0;
    $fZLevel = -$oOrigin->fZ / $oDirection->fZ;

    // Check if trace maybe hits floor
    if (0.01 < $fZLevel) {
        $fTraceDistance = $fZLevel;
        $oNormal   = Scene::$oNormalUp;
        $iMaterial = 1;
    }

    foreach (Scene::$aObjects as $oObject) {
        $oPoint    = $oOrigin->iSub($oObject);
        $fDot      = $oPoint->dot($oDirection);
        $fEye      = $oPoint->dot($oPoint) - 8.0;
        $fIntrSqrd = $fDot * $fDot - $fEye;

        if ($fIntrSqrd > 0.0) {
            $fObjectDistance = -$fDot - sqrt($fIntrSqrd);
            if ($fObjectDistance < $fTraceDistance && $fObjectDistance > 0.01) {
                $fTraceDistance = $fObjectDistance;
                $oNormal = $oDirection
                    ->iScale($fTraceDistance)
                    ->add($oPoint);
                $iMaterial = 2;
                break;
            }
        }
    }
    if ($oNormal && $oNormal !== Scene::$oNormalUp) {
        $oNormal->normalise();
    }
    return $iMaterial;
}

///////////////////////////////////////////////////////////////////////////////

// Sampling
function sample(Vec3F $oOrigin, Vec3F $oDirection) : Vec3F {

    static $iRecursion = 0;

    if (++$iRecursion > 3) {
        --$iRecursion;
        return Scene::$oBlack;
    }

    // Find where the ray intersects the world
    $iMaterial = trace($oOrigin, $oDirection, $fTraceDistance, $oNormal);

    // Hit nothing? Sky shade
    if ($iMaterial === 0) {
        $fGradient = 1.0 - $oDirection->fZ;
        $fGradient *= $fGradient;
        $fGradient *= $fGradient;
        --$iRecursion;
        return Scene::$oSkyRGB->iScale($fGradient); // Blueish sky colour
    }

    $oIntersect = $oDirection
        ->iScale($fTraceDistance)
        ->add($oOrigin);

    // Calculate the lighting vector
    $oLight = Scene::$oLight->iSub($oIntersect);
    $oLight->fX += frand();
    $oLight->fY += frand();
    $oLight->normalise();

    $oHalfVector = $oNormal
        ->iScale($oNormal->dot($oDirection) * -2.0)
        ->add($oDirection);

    // Calculate the lambertian illumuination factor
    $fLambertian = $oLight->dot($oNormal);

    if ($fLambertian < 0 || trace($oIntersect, $oLight, $fTraceDistance, $oNormal)) {
        $fLambertian = 0; // in shadow
    }

    // Hit the floor plane
    if ($iMaterial === 1) {
        $oIntersect->scale(0.2);
        --$iRecursion;
        return (
            // Compute check colour based on the position
            (int) (ceil($oIntersect->fX * 0.5) + ceil($oIntersect->fY * 0.5)) & 1 ?
            Scene::$oFloorRGB1 :
            Scene::$oFloorRGB2   // white
        )->iScale($fLambertian * 0.2 + 0.1);

    }

    // Hit a sphere? Bounce it
    $oRGB = sample($oIntersect, $oHalfVector)->iScale(0.75);

    if ($fLambertian > 0) {
        // Compute the specular highlight power
        $fSpecular = pow($oLight->dot($oHalfVector), 99.0);
        $oRGB->fX += $fSpecular;
        $oRGB->fY += $fSpecular;
        $oRGB->fZ += $fSpecular;
    }

    --$iRecursion;
    return $oRGB;
}

///////////////////////////////////////////////////////////////////////////////

// Main
function main() {
    $mark = microtime(true);
    $iWidth  = 100;
    $iHeight = 100;

    $fImageScale = 1.6 / $iWidth;

    Scene::init();

    // camera direction vectors
    $oCameraForward = (new Vec3F(-6.0, -16.0, 0.0))->normalise();
    $oCameraUp = Scene::$oNormalUp
        ->iCross($oCameraForward)
        ->normalise()
        ->scale($fImageScale);

    $oCameraRight = $oCameraForward
        ->iCross($oCameraUp)
        ->normalise()
        ->scale($fImageScale);

    $oEyeOffset = $oCameraUp
        ->iAdd($oCameraRight)
        ->scale(-0.5 * $iWidth)
        ->add($oCameraForward);



    $nFrames = 32;
    $fStep   = M_PI / 32.0;
    $fTime   = 0.0;

    $oDisplay = PDE\Display\Factory::get()->create('DoubleVerticalRGB', $iWidth, $iHeight);
    $oBlitter = new PDE\Graphics\Blitter();
    $oBlitter->setTarget($oDisplay);

    $aFrames = [];

    while ($nFrames--) {

        $oFrame = new PDE\Graphics\Image($iWidth, $iHeight);

        Scene::$aObjects[0]->fZ = 4 + 8.0 * abs(cos($fTime));
        Scene::$aObjects[1]->fZ = 4 + 8.0 * abs(cos($fTime + M_PI / 4.0));
        $fTime += $fStep;

        $oPixels = $oFrame->getPixels();
        $iPixel  = 0;
        for ($iPixelY = $iHeight; $iPixelY--;) {
            for ($iPixelX = $iWidth; $iPixelX--;) {

                // Use a vector for the pixel. The values here are in the range 0.0 - 255.0 rather than the 0.0 - 1.0
                $oPixel = Scene::$oBlack->clone();
                $iRays = 8;
                while ($iRays--) {

                    // Random delta to be added for depth of field effects
                    $oDelta = $oCameraUp
                        ->iScale((frand() - 0.5) * 20.0)
                        ->add(
                            $oCameraRight
                                ->iScale((frand() - 0.5) * 20.0)
                        );


                    // Accumulate the sample result into the current pixel
                    $oPixel->add(
                        sample(
                            Scene::$oFocalPoint->iAdd($oDelta),
                            $oCameraUp
                                ->iScale(frand() + $iPixelX)
                                ->add(
                                    $oCameraRight
                                        ->iScale(frand() + $iPixelY)
                                        ->add($oEyeOffset)
                                )
                                ->scale(16.0)
                                ->sub($oDelta)
                                ->normalise()
                        )
                    );
                }
                $oPixel
                    ->scale(35.0)
                    ->add(Scene::$oAmbientRGB);

                // Convert to integers and push out to ppm outpu stream
                $oPixels[$iPixel++] =  (int)min($oPixel->fX, 255) << 16 | (int)min($oPixel->fY, 255) << 8 | (int)min($oPixel->fZ, 255);
            }
        }
        $aFrames[] = $oFrame;
        $oDisplay->clear();
        $oBlitter->setSource($oFrame)
            ->copy(
                0,
                0,
                0,
                0,
                $oFrame->getWidth(),
                $oFrame->getHeight()
            );
        $oDisplay->redraw();
        $oDisplay->waitForFrame();
        echo PDE\Display\IANSIControl::ATTR_RESET, "Rendered frame ", count($aFrames), "...";
    }


    while (true) {
        foreach ($aFrames as $oFrame) {
            $oDisplay->clear();
            $oBlitter->setSource($oFrame)
                ->copy(
                    0,
                    0,
                    0,
                    0,
                    $oFrame->getWidth(),
                    $oFrame->getHeight()
                );
            $oDisplay->redraw();
            usleep(25000);
        }
    }

}

main();




