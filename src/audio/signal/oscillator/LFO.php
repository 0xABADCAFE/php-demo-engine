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

namespace ABadCafe\PDE\Audio\Signal\Oscillator;

use ABadCafe\PDE\Audio;

/**
 * Basic LFO, operating in -1.0 to 1.0 range (depending on waveform)
 */
class LFO extends Base {

    const
        MIN_FREQUENCY = 1/60.0,
        DEF_FREQUENCY = 2.0,
        MAX_FREQUENCY = 64.0
    ;

    protected float
        $fDepth = 0.5,
        $fBias  = 0.0
    ;

    /**
     * Constructor
     *
     * @param Audio\Signal\IWaveform|null $oWaveform
     * @param float $fFrequency
     * @param float $fDepth
     */
    public function __construct(
        ?Audio\Signal\IWaveform $oWaveform = null,
        float $fFrequency = self::DEF_FREQUENCY,
        float $fDepth     = 0.5
    ) {
        parent::__construct($oWaveform, $fFrequency, 0.0);
        $this->fDepth = $fDepth;
    }

    /**
     * Set the depth.
     */
    public function setDepth(float $fDepth): self {
        $this->fDepth = $fDepth;
        return $this;
    }

    /**
     * Calculates a new audio packet
     *
     * @return Signal\Audio\Packet;
     */
    protected function emitNew(): Audio\Signal\Packet {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this->oWaveformInput[$i] = $this->fScaleVal * $this->iSamplePosition++;
        }
        return $this->oLastOutput = $this->oWaveform
            ->map($this->oWaveformInput)
            ->scaleBy($this->fDepth);
        ;
    }
}

