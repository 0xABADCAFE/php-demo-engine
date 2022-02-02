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
 * LFO operating in range 0.0 - 1.0
 */
class LFOZeroToOne extends LFO {

    /**
     * @inheritDoc
     */
    protected function emitNew(): Audio\Signal\Packet {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this->oWaveformInput[$i] = $this->fScaleVal * $this->iSamplePosition++;
        }
        return $this->oLastOutput = $this->oWaveform // @phpstan-ignore-line : false positive, member not null here
            ->map($this->oWaveformInput)
            ->scaleBy(0.5 * $this->fDepth)
            ->biasBy(0.5);
        ;
    }
}

