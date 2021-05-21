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

namespace ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * DecayPulse
 *
 * Calculates the continuous Signal\Packet stream for an envelope defined by an exponential decay curve.
 */
class DecayPulseSoft extends DecayPulse {

    // TODO - consider note maps for these
    protected float $fPrevious   = 0.0;

    /**
     * @inheritDoc
     */
    public function reset() : self {
        parent::reset();
        return $this;
    }

    /**
     * Emit the next signal Packet.
     *
     * @return Signal\Control\Packet
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        if ($this->useLast($iIndex)) {
            return $this->oOutputPacket;
        }
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this->fCurrent *= $this->fDecayPerSample;
            $this->oOutputPacket[$i] = $this->fPrevious = 0.5 * ($this->fCurrent + $this->fPrevious);
        }
        $this->iSamplePosition += Audio\IConfig::PACKET_SIZE;
        return $this->oOutputPacket;
    }
}

