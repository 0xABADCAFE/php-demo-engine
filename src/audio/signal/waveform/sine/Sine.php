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
use function \sin;

/**
 * Sine
 *
 * Vanilla sine wave implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Sine implements Signal\IWaveform {

    use Util\TAlwaysShareable;

    /**
     * Waveform period (interval after which it repeats).
     */
    const PERIOD = 2.0 * M_PI;

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
            $oOutput[$i] = sin($fTime); // @phpstan-ignore-line - false positive
        }
        return $oOutput;
    }

    /**
     * @inheritDoc
     */
    public function value(float $fInput): float {
        return sin($fInput);
    }
}
