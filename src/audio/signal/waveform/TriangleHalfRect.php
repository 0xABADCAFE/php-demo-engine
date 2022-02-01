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
 * Triangle
 *
 * Triangle implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class TriangleHalfRect implements Signal\IWaveform {

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
        $fHalf   = 0.5;
        foreach ($oInput as $i => $fTime) {
            $fTime   -= $fHalf;
            $fFloor  = floor($fTime);
            $fScale  = (int)$fFloor & 1 ? 4.0 : -4.0;
            $fSample = $fScale * ($fTime - $fFloor - $fHalf);
            $oOutput[$i] = ($fSample > 0 ? $fSample : 0.0) - 1.0;
        }
        return $oOutput;
    }

    /**
     * @inheritDoc
     */
    public function value(float $fTime): float {
        $fTime   -= 0.5;
        $fFloor  = floor($fTime);
        $fScale  = (int)$fFloor & 1 ? 4.0 : -4.0;
        $fSample = $fScale * ($fTime - $fFloor - 0.5);
        return ($fSample > 0 ? $fSample : 0.0) - 1.0;
    }
}
