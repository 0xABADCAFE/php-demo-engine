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

namespace ABadCafe\PDE\Audio\Signal\Waveform;
use ABadCafe\PDE\Audio\Signal;
use ABadCafe\PDE\Util;
use function \floor;

/**
 * AliasedSquare
 *
 * Square implementation of IWaveform.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Square implements IHardTransient {

    use Util\TAlwaysShareable;

    /**
     * Waveform period (interval after which it repeats).
     */
    const PERIOD = 2.0;

    /**
     * @inheritDoc
     */
    public function getPeriod(): float {
        return self::PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Signal\Packet $oInput): Signal\Packet {
        $oOutput = clone $oInput;
        foreach ($oInput as $i => $fTime) {
            /** @var float $fTime */
            $oOutput[$i] = (int)floor($fTime) & 1 ? -1.0 : 1.0;
        }
        return $oOutput;
    }

    /**
     * @inheritDoc
     */
    public function value(float $fTime): float {
        return (int)floor($fTime) & 1 ? -1.0 : 1.0;
    }
}
