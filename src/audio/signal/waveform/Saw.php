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
use function \ceil;

/**
 * Saw
 *
 * Saw implementation of IWaveform.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Saw implements IHardTransient {

    use Util\TAlwaysShareable;

    /**
     * Waveform period (interval after which it repeats).
     */
    const PERIOD = 1.0;

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
            $oOutput[$i] = 2.0 * (ceil($fTime) - $fTime - 0.5);
        }
        return $oOutput;
    }

    /**
     * @inheritDoc
     */
    public function value(float $fTime): float {
        return 2.0 * (ceil($fTime) - $fTime - 0.5);
    }
}
