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
use ABadCafe\PDE\Audio;
use ABadCafe\PDE\Audio\Signal;

use function \sin;

/**
 * SinePinch
 *
 * Sinrwave implementation of IWaveform
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class SinePinch implements Signal\IWaveform {

    /**
     * Waveform period (interval after which it repeats).
     */
    const PERIOD = 4.0;
    private const ADJUST = 0.5 * M_PI;

    const TRANSFORM = [
        // Quadrant phase shift, Bias Adjust, Scale.
        // This default configuration rearranges a sine wave into something resembling a triangle.
        [ 3.0,  1.0, 1.0],
        [ 1.0,  1.0, 1.0],
        [-1.0, -1.0, 1.0],
        [-3.0, -1.0, 1.0]
    ];


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

        $oRephase = clone $oInput;

        $aTransform = static::TRANSFORM;

        // Mutate the quadrant phase
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase        = $oInput[$i];
            $iQuadrant     = ((int)$fPhase) & 3;
            $fPhase       += $aTransform[$iQuadrant][0];
            $fPhase       *= self::ADJUST;
            $oRephase[$i] = $fPhase;
        }

        $oOutput = clone $oInput;

        // Mutate the quadrant bias
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase      = $oInput[$i];
            $fSin        = sin($oRephase[$i]);
            $iQuadrant   = ((int)$fPhase) & 3;
            $oOutput[$i] = ($fSin * $aTransform[$iQuadrant][2]) + $aTransform[$iQuadrant][1];
        }
        return $oOutput;
    }
}
