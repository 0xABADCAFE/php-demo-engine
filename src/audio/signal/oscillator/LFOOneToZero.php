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
 * LFO that has a flat output of 1.0 which increasingly oscillates towards zero as the depth is increased
 */
class LFOOneToZero extends LFO {

    /**
     * @inheritDoc
     */
    protected function emitNew(): Audio\Signal\Packet {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this->oWaveformInput[$i] = $this->fScaleVal * $this->iSamplePosition++;
        }
        return $this->oLastOutput = $this->oWaveform // @phpstan-ignore-line : false positive
            ->map($this->oWaveformInput)
            ->scaleBy(0.5 * $this->fDepth)
            ->biasBy(1.0 - $this->fDepth);
        ;
    }
}

