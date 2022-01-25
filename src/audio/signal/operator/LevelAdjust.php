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

namespace ABadCafe\PDE\Audio\Signal\Operator;
use ABadCafe\PDE\Audio;
use function \abs;

/**
 * LevelAdjust
 *
 * Fixed level adjustment, for attenuation or amplification.
 */

/**
 * @template T of Audio\Signal\IStream
 */
class LevelAdjust implements Audio\Signal\IStream {

    use Audio\Signal\TStream;

    /** @var T $oStream */
    private Audio\Signal\IStream $oStream;

    private float $fLevel;

    private bool  $bMuted = false;

    /**
     * Constructor
     *
     * @param T     $oStream
     * @param float $fLevel
     */
    public function __construct(Audio\Signal\IStream $oStream, float $fLevel) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setLevel($fLevel);
    }

    /**
     * @return T
     */
    public function getStream(): Audio\Signal\IStream {
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
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        if ($this->bEnabled && !$this->bMuted) {
            return $this->oStream
                ->emit($iIndex)
                ->scaleBy($this->fLevel);
        } else {
            return $this->emitSilence();
        }
    }
}
