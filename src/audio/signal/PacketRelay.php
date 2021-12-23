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
 * PacketRelay
 *
 * Adapter that allows a non-stream immediate source of packets to be used with an IStream consumer.
 */
class PacketRelay implements IStream {

    private int $iPosition = 0;

    private Packet $oPacket;

    /**
     * Constructor
     */
    public function __construct() {
        $this->oPacket = Packet::create();
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->iPosition;
    }

    /**
     * @param  Packet<float> $oPacket
     * @return self
     */
    public function setPacket(Packet $oPacket): self {
        $this->oPacket = $oPacket;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->iPosition = 0;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;
        return $this->oPacket;
    }

    /**
     * @inheritDoc
     */
    public function enable(): IStream {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disable(): IStream {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool {
        return true;
    }
}
