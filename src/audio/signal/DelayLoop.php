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
use \SPLDoublyLinkedList;

/**
 * DelayLoop
 *
 *
 */
class DelayLoop implements IStream {

    use TStream;

    private ?IStream            $oStream;
    private PacketRelay         $oRelay;
    private IFilter             $oFilter;
    private SPLDoublyLinkedList $oQueue;

    private float
        $fFeedback,
        $fCutoff,
        $fResonance,
        $fDryLevel
    ;

    /**
     * Constructor
     */
    public function __construct(
        ?IStream $oStream = null,
        float $fDelayMs   = 375.0,
        float $fFeedback  = -0.3,
        float $fDryLevel  = 1.0,
        float $fCutoff    = 0.3,
        float $fResonance = 0.0
    ) {
        self::initStreamTrait();
        $this->oStream    = $oStream;
        $this->fFeedback  = $fFeedback;
        $this->fDryLevel  = $fDryLevel;
        $this->fCutoff    = $fCutoff;
        $this->fResonance = $fResonance;

        $this->oRelay     = new PacketRelay();
        $this->oFilter    = new Filter\LowPass(
            $this->oRelay,
            $fCutoff,
            $fResonance
        );

        $this->oQueue      = new SPLDoublyLinkedList;
        $fPacketDurationMs = 1000.0 * Audio\IConfig::PACKET_SIZE / Audio\IConfig::PROCESS_RATE;
        $iMaxPackets = (int)ceil($fDelayMs / $fPacketDurationMs);
        for ($i = 0; $i < $iMaxPackets; ++$i) {
            $this->oQueue->add($i, Packet::create());
        }
    }

    public function setStream(IStream $oStream) : self {
        $this->oStream    = $oStream;
        return $this;
    }

    public function setFeedback(float $fFeedback) : self {
        $this->fFeedback = $fFeedback;
        return $this;
    }

    public function setCutoff(float $fCutoff) : self {
        $this->fCutoff = $fCutoff;
        $this->oFilter->setCutoff($fCutoff);
        return $this;
    }

    public function setResonance(float $fResonance) : self {
        $this->fResonance = $fResonance;
        $this->oFilter->setResonance($fResonance);
        return $this;
    }

    public function getStream() : IStream {
        return $this->oStream;
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
}
