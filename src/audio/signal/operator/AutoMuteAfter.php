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
 * AutoMuteAfter
 *
 * Wrapper for another Audio\Signal\IStream that automatically disables itself after a certain amount of time. This is to limit the
 * duration that sound generators run for, especially one-shot sounds. This prevents wasting of CPU when a generator
 * would otherwise be calculating (near) silence.
 */

/**
 * @template T of Audio\Signal\IStream
 */
class AutoMuteAfter implements Audio\Signal\IStream {

    use Audio\Signal\TStream;

    /** @var T $oStream */
    private Audio\Signal\IStream $oStream;

    private int
        $iDisableAfter = 0, // in samples
        $iPosition     = 0
    ;

    /**
     * Constructor
     *
     * @param T     $oStream
     * @param float $fSeconds
     */
    public function __construct(Audio\Signal\IStream $oStream, float $fSeconds) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setDisableAfter($fSeconds);
    }

    /**
     * Set the duration, in seconds, after which the wrapped Audio\Signal\IStream will be disabled. Values <= 0 will never disable.
     *
     * @param  float $fSeconds
     * @return self<T>
     */
    public function setDisableAfter(float $fSeconds): self {
        $this->iDisableAfter = $fSeconds > 0.0 ? ((int)($fSeconds * Audio\IConfig::PROCESS_RATE)) : 0;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Reports the stream position of the wrapped stream rather than the wrapper.
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
        $this->iPosition = 0;
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;

        // Check to see if the stream needs disabling yet
        $this->bEnabled =
            $this->bEnabled &&
            $this->iDisableAfter > 0 &&
            $this->iPosition < $this->iDisableAfter
        ;

        if ($this->bEnabled) {
            return $this->oStream->emit($iIndex);
        } else {
            return $this->emitSilence();
        }
    }

    /**
     * @return T
     */
    public function getStream(): Audio\Signal\IStream {
        return $this->oStream;
    }
}
