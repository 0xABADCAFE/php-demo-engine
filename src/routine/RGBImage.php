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

/**
 * Display an image
 *
 */
class RGBImage extends Base implements IResourceLoader {

    use TResourceLoader;

    private Graphics\Image   $oImage;
    private Graphics\Blitter $oBlitter;

    private float $fDisplacementX = 0.0, $fDisplacementY = 0.0;

    const DEFAULT_PARAMETERS = [
        'sPath'   => 'required',
        'iTop'    => 0,
        'iLeft'   => 0,
        'iMode'   => Graphics\Blitter::MODE_REPLACE,
        'aPath'   => []
    ];

    /**
     * Basic constructor
     *
     * @implements IRoutine::__construct()
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        $this->oBlitter = new Graphics\Blitter();
        parent::__construct($oDisplay, $aParameters);
    }

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
        $this->oBlitter->setTarget($oDisplay);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            $this->oBlitter
                ->setMode($this->oParameters->iMode)
                ->copy(
                    0,
                    0,
                    $this->oParameters->iLeft,
                    $this->oParameters->iTop,
                    $this->oImage->getWidth(),
                    $this->oImage->getHeight()
                );
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
            $iWidth       = (int)$aMatches[1];
            $iHeight      = (int)$aMatches[2];
            $this->oImage = new Graphics\Image($iWidth, $iHeight);
            $iArea        = $iWidth * $iHeight;
            $sData        = substr($sRaw, ($iArea * -3));
            $iDataOffset  = 0;
            $oPixels      = $this->oImage->getPixels();
            for ($i = 0; $i < $iArea; ++$i) {
                $oPixels[$i] =
                    (ord($sData[$iDataOffset++]) << 16) |
                    (ord($sData[$iDataOffset++]) << 8) |
                    (ord($sData[$iDataOffset++]));
            }
            $this->oBlitter->setSource($this->oImage);

        } else {
            throw new \Exception('Invalid PNM Format');
        }
    }

}
