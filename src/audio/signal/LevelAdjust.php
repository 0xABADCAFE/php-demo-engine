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
 * Fixed level adjustment
 */
class LevelAdjust implements IStream {

    use TStream;

    private IStream $oStream;

    private float $fLevel;

    /**
     * Constructor
     *
     * @param float $fOutLevel
     */
    public function __construct(IStream $oStream, float $fLevel) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setLevel($fLevel);
    }

    public function getStream() : IStream {
        return $this->oStream;
    }

    public function getLevel() : float {
        return $this->fLevel;
    }

    /**
     * Set the level adjustment to apply.
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
