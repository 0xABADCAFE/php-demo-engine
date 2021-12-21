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
 */
class RGBImage extends Base implements IResourceLoader {

    use TResourceLoader;

    private Graphics\Image   $oImage;
    private Graphics\Blitter $oBlitter;

    //private float $fDisplacementX = 0.0, $fDisplacementY = 0.0;

    const DEFAULT_PARAMETERS = [
        'sPath'   => 'required',
        'iTop'    => 0,
        'iLeft'   => 0,
        'iMode'   => Graphics\Blitter::MODE_REPLACE,
        'aPath'   => []
    ];

    /**
     * @inheritDoc
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        $this->oBlitter = new Graphics\Blitter();
        parent::__construct($oDisplay, $aParameters);
    }

    /**
     * @inheritDoc
     */
    public function preload(): self {
        $this->oImage = $this->loadPNM($this->oParameters->sPath);
        $this->oBlitter->setSource($this->oImage);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        if ($this->bCanRender  = ($oDisplay instanceof PDE\Display\IPixelled)) {
            $this->oBlitter->setTarget($oDisplay);
        }
        $this->oDisplay    = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
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
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {

    }

}
