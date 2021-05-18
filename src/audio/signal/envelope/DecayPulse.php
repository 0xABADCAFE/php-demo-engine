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
class DecayPulse implements Audio\Signal\IEnvelope {

    use Audio\Signal\TPacketIndexAware;

    private Audio\Signal\Packet $oOutputPacket;

    private float
        $fInitial,          // User supplied initial level. Used value depends on key scaling (if any)
        $fHalfLife,         // User supplied half-life. Used value depends on key scaling (if any)
        $fCurrent,
        $fDecayPerSample    // Calculated decay, per sample.
    ;

    private int $iSamplePosition = 0;

    // TODO - consider note maps for these
    private float
        $fTimeScale  = 1.0,
        $fLevelScale = 1.0
    ;

    /**
     * Constructor
     *
     * @param float $fInitial
     * @param float $fHalfLife (in seconds)
     */
    public function __construct(
        float $fInitial,
        float $fHalfLife
    ) {
        $this->oOutputPacket  = Audio\Signal\Packet::create();
        $this->fInitial       = $fInitial;
        $this->fHalfLife      = $fHalfLife;
        $this->reset();
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->iSamplePosition;
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->iSamplePosition = 0;
        $this->recalculate();
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
            $this->oOutputPacket[$i] = $this->fCurrent;
            ++$this->iSamplePosition;
        }
        return $this->oOutputPacket;
    }

    /**
     * Recalculate the internal values
     */
    private function recalculate() {

        // First the easiest calculation which is the initial level to use.
        $this->fCurrent = $this->fInitial * $this->fLevelScale;

        // Calculate the effective half life in samples.
        // This is the sample rate * half life * key scaling factor
        $iHalfLifeInSamples = (int)(Audio\IConfig::PROCESS_RATE * $this->fHalfLife * $this->fTimeScale);

        // Now calculate the required decay per sample required to reach half intensity after that many samples.
        $this->fDecayPerSample = 0.5 * 2 ** (($iHalfLifeInSamples - 1) / $iHalfLifeInSamples);
    }

}

