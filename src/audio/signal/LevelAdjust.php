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

use function \abs;

/**
 * LevelAdjust
 *
 * Fixed level adjustment, for attenuation or amplification.
 */

/**
 * @template T of IStream
 */
class LevelAdjust implements IStream {

    use TStream;

    /** @var T $oStream */
    private IStream $oStream;

    private float $fLevel;

    private bool  $bMuted = false;

    /**
     * Constructor
     *
     * @param T     $oStream
     * @param float $fLevel
     */
    public function __construct(IStream $oStream, float $fLevel) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setLevel($fLevel);
    }

    /**
     * @return T
     */
    public function getStream(): IStream {
        return $this->oStream;
    }

    /**
     * @return float
     */
    public function getLevel(): float {
        return $this->fLevel;
    }

    /**
     * Set the level adjustment to apply.
     *
     * @param  float $fLevel
     * @return self<T>
     */
    public function setLevel(float $fLevel): self {
        $this->fLevel  = $fLevel;
        $this->bMuted  = abs($fLevel) < 1e-5;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->oStream->getPosition();
    }

    /**
     * @inheritDoc
     *
     * @return self<T>
     */
    public function reset(): self {
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Packet {
        if ($this->bEnabled && !$this->bMuted) {
            return $this->oStream
                ->emit($iIndex)
                ->scaleBy($this->fLevel);
        } else {
            return $this->emitSilence();
        }
    }
}
