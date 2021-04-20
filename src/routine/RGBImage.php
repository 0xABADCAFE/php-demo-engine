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
 * Display an image
 *
 */
class RGBImage extends Base implements IResourceLoader {

    use TResourceLoader;

    private int $iWidth, $iHeight, $iViewWidth, $iViewHeight;

    private SPLFixedArray $oPixels;

    private Utils\Blitter $oBlitter;

    const DEFAULT_PARAMETERS = [
        'sPath'   => 'required',
        'iTop'    => 0,
        'iLeft'   => 0,
        'iMode'   => Utils\Blitter::DM_SET,
        'fXSpeed' => 0.0,
        'fYSpeed' => 0.0,
    ];

    /**
     * Basic constructor
     *
     * @implements IRoutine::__construct()
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        $this->oBlitter = new Utils\Blitter;
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
        $this->iViewWidth  = $oDisplay->getWidth();
        $this->iViewHeight = $oDisplay->getHeight();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex) : self {
        if ($this->canRender($iFrameNumber, $fTimeIndex)) {
            $this->oBlitter
                ->setTarget(
                    $this->oDisplay->getPixelBuffer(),
                    $this->iViewWidth,
                    $this->iViewHeight
                )
                ->setDrawMode($this->oParameters->iMode)
                ->copy(
                    0,
                    0,
                    (int)($this->oParameters->iLeft + $this->oParameters->fXSpeed * $iFrameNumber),
                    (int)($this->oParameters->iTop  + $this->oParameters->fYSpeed * $iFrameNumber),
                    $this->iWidth,
                    $this->iHeight
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
            $this->iWidth  = (int)$aMatches[1];
            $this->iHeight = (int)$aMatches[2];
            $iArea         = $this->iWidth * $this->iHeight;
            $this->oPixels = new SPLFixedArray($iArea);
            $sData         = substr($sRaw, ($iArea * -3));
            $iDataOffset   = 0;
            for ($i = 0; $i < $iArea; ++$i) {
                $this->oPixels[$i] =
                    (ord($sData[$iDataOffset++]) << 16) |
                    (ord($sData[$iDataOffset++]) << 8) |
                    (ord($sData[$iDataOffset++]));
            }
            $this->oBlitter->setSource($this->oPixels, $this->iWidth, $this->iHeight);

        } else {
            throw new \Exception('Invalid PNM Format');
        }
    }

}
