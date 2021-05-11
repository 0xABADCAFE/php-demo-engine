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
        'iMode'   => self::MODE_RECORD,
        'iWidth'  => 100,
        'iHeight' => 100
    ];


    /** @var PDE\Graphics\Image[] $aFrames */
    private array $aFrames = [];


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
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange() {

    }

}
