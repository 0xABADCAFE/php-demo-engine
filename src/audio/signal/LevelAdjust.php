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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

/**
 * LevelAdjust
 *
 * Fixed level adjustment, for attenuation or amplification.
 */
class LevelAdjust implements IStream {

    use TStream;

    private IStream $oStream;

    private float $fLevel;

    /**
     * Constructor
     *
     * @param IStream $oStream
     * @param float   $fOutLevel
     */
    public function __construct(IStream $oStream, float $fLevel) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setLevel($fLevel);
    }

    /**
     * @return IStream
     */
    public function getStream() : IStream {
        return $this->oStream;
    }

    /**
     * @return float
     */
    public function getLevel() : float {
        return $this->fLevel;
    }

    /**
     * Set the level adjustment to apply.
     *
     * @param  float $fLevel
     * @return self
     */
    public function setLevel(float $fLevel) : self {
        $this->fLevel  = $fLevel;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->oStream->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Packet {
        if ($this->bEnabled) {
            return $this->oStream
                ->emit($iIndex)
                ->scaleBy($this->fLevel);
        } else {
            return $this->emitSilence();
        }
    }
}
