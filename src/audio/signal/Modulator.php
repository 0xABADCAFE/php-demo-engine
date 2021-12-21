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
 * Fixed level mixer for multiple streams.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Modulator implements IStream {

    use TStream, TPacketIndexAware;

    private int     $iPosition = 0;
    private IStream $oStream1, $oStream2;
    private Packet  $oLastPacket;

    /**
     * Constructor
     *
     * @param float $fOutLevel
     */
    public function __construct(IStream $oStream1, IStream $oStream2) {
        self::initStreamTrait();
        $this->oLastPacket = Packet::create();
        $this->oStream1 = $oStream1;
        $this->oStream2 = $oStream2;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->iPosition;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->iPosition  = 0;
        $this->iLastIndex = 0;
        $this->oStream1->reset();
        $this->oStream2->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;
        if (!$this->bEnabled) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oLastPacket;
        }
        return $this->emitNew();
    }

    /**
     * @return Packet
     */
    private function emitNew(): Packet {
        $this->oLastPacket = $this->oStream1->emit($this->iLastIndex);
        return $this->oLastPacket->modulateWith($this->oStream2->emit($this->iLastIndex));
    }
}
