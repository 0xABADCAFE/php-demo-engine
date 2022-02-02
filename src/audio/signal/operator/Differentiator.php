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

/**
 * Differentiator
 *
 * Turns the signal into it's equivalent delta stream
 */

/**
 * @template T of Audio\Signal\IStream
 */
class Differentiator implements Audio\Signal\IStream {

    use Audio\Signal\TStream;

    /** @var T $oStream */
    private Audio\Signal\IStream $oStream;

    private float $fCarry = 0.0;

    private bool  $bMuted = false;

    /**
     * Constructor
     *
     * @param T     $oStream
     */
    public function __construct(Audio\Signal\IStream $oStream) {
        self::initStreamTrait();
        $this->oStream = $oStream;
    }

    /**
     * @return T
     */
    public function getStream(): Audio\Signal\IStream {
        return $this->oStream;
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
        $this->fCarry = 0;
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        if ($this->bEnabled && !$this->bMuted) {
            $oPacket = $this->oStream
                ->emit($iIndex);
            $fLast = $this->fCarry;
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                /** @var float $fNext */
                $fNext       = $oPacket[$i];
                $oPacket[$i] = $fNext - $fLast;
                $fLast = $fNext;
            }
            $this->fCarry = $fLast;
            return $oPacket;
        } else {
            return $this->emitSilence();
        }
    }
}
