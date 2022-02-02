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
 * Fixed level mixer for multiple streams.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Modulator implements Audio\Signal\IStream {

    use Audio\Signal\TStream, Audio\Signal\TPacketIndexAware;

    private int     $iPosition = 0;
    private Audio\Signal\IStream $oStream1, $oStream2;
    private Audio\Signal\Packet  $oLastPacket;

    /**
     * Constructor
     *
     * @param Audio\Signal\IStream $oStream1
     * @param Audio\Signal\IStream $oStream2
     */
    public function __construct(Audio\Signal\IStream $oStream1, Audio\Signal\IStream $oStream2) {
        self::initStreamTrait();
        $this->oLastPacket = Audio\Signal\Packet::create();
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
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
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
     * @return Audio\Signal\Packet
     */
    private function emitNew(): Audio\Signal\Packet {
        $this->oLastPacket = $this->oStream1->emit($this->iLastIndex);
        return $this->oLastPacket->modulateWith($this->oStream2->emit($this->iLastIndex));
    }
}
