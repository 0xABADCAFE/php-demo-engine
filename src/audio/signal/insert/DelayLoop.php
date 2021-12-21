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

namespace ABadCafe\PDE\Audio\Signal\Insert;
use ABadCafe\PDE\Audio;
use \SPLDoublyLinkedList;

use function \ceil;

/**
 * DelayLoop
 *
 * A delay loop with low pass filter.
 */
class DelayLoop implements Audio\Signal\IInsert {

    use Audio\Signal\TStream;

    private ?Audio\Signal\IStream    $oStream;
    private Audio\Signal\PacketRelay $oRelay;
    private Audio\Signal\IFilter     $oFilter;
    private SPLDoublyLinkedList      $oQueue;

    private float
        $fFeedback,
        $fCutoff,
        $fResonance,
        $fDryLevel
    ;

    /**
     * Constructor
     *
     * @param Audio\Signal\IStream|null $oStream
     * @param float $fDelayMs
     * @param float $fFeedback
     * @param float $fDryLevel
     * @param float $fCutoff
     * @param float $fResonance
     */
    public function __construct(
        ?Audio\Signal\IStream $oStream = null,
        float $fDelayMs                = 375.0,
        float $fFeedback               = -0.3,
        float $fDryLevel               = 1.0,
        float $fCutoff                 = 0.3,
        float $fResonance              = 0.0
    ) {
        self::initStreamTrait();
        $this->oStream    = $oStream;
        $this->fFeedback  = $fFeedback;
        $this->fDryLevel  = $fDryLevel;
        $this->fCutoff    = $fCutoff;
        $this->fResonance = $fResonance;
        $this->oRelay     = new Audio\Signal\PacketRelay();
        $this->oFilter    = new Audio\Signal\Filter\LowPass(
            $this->oRelay,
            $fCutoff,
            $fResonance
        );
        $this->createQueue($fDelayMs);
    }

    /**
     * @return float
     */
    public function getFeedback() : float {
        return $this->fFeedback;
    }

    /**
     * @param  float
     * @return self
     */
    public function setFeedback(float $fFeedback) : self {
        $this->fFeedback = $fFeedback;
        return $this;
    }

    /**
     * @return float
     */
    public function getCutoff() : float {
        return $this->fCutoff;
    }

    /**
     * @param  float
     * @return self
     */
    public function setCutoff(float $fCutoff) : self {
        $this->fCutoff = $fCutoff;
        $this->oFilter->setCutoff($fCutoff);
        return $this;
    }

    /**
     * @return float
     */
    public function getResonance() : float {
        return $this->fResonance;
    }

    /**
     * @param  float
     * @return self
     */
    public function setResonance(float $fResonance) : self {
        $this->fResonance = $fResonance;
        $this->oFilter->setResonance($fResonance);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInputStream() : ?Audio\Signal\IStream {
        return $this->oStream;
    }

    /**
     * @inheritDoc
     */
    public function setInputStream(?Audio\Signal\IStream $oStream) : self {
        $this->oStream = $oStream;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDryLevel() : float {
        return $this->fDryLevel;
    }

    /**
     * @inheritDoc
     */
    public function setDryLevel(float $fDryLevel) : self {
        $this->fDryLevel = $fDryLevel;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->oStream ? $this->oStream->getPosition() : 0;
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->oStream && $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        if ($this->oStream && $this->bEnabled) {
            // Get the dry signal
            $oDry    = $this->oStream->emit($iIndex);

            // Get the delay signal from the tail of the delay line
            $oDelay  = $this->oQueue->pop();

            // Filter the dry signal packet
            $this->oRelay->setPacket($oDry);
            $oFiltered = clone $this->oFilter->emit();

            // Mix the filtered signal with the delay and push into the head of the delay line.
            $oFiltered
                ->sumWith($oDelay)
                ->scaleBy($this->fFeedback);
            $this->oQueue->unshift($oFiltered);

            return $oDry
                ->scaleBy($this->fDryLevel)
                ->sumWith($oDelay);
        } else {
            return $this->emitSilence();
        }
    }

    /**
     * Create the queue
     *
     * @param float $fDelayMs
     */
    public function createQueue(float $fDelayMs) {
        $this->oQueue      = new SPLDoublyLinkedList;
        $fPacketDurationMs = 1000.0 * Audio\IConfig::PACKET_PERIOD;
        $iMaxPackets       = (int)\ceil($fDelayMs / $fPacketDurationMs);
        for ($i = 0; $i < $iMaxPackets; ++$i) {
            $this->oQueue->add($i, Audio\Signal\Packet::create());
        }
    }
}
