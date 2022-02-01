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
use ABadCafe\PDE\Util;
use function \sin;

/**
 * SineXForm
 *
 * Transformed Sine Wave. Divides the sine wave duty cycle into quadrants that can be shifted about, scaled and
 * biased to produce interesting variations. This is a direct realisation of the quadrant mutator idea from GIMPS
 * implemented in-line for performance.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
abstract class SineXForm implements Signal\IWaveform {

    use Util\TAlwaysShareable;

    /**
     * Waveform period (interval after which it repeats). Can be overridden.
     */
    const PERIOD = 4.0;

    /**
     * Implementors should provide their own transformation matrix
     */
    const TRANSFORM = [
        // Quadrant phase shift, Bias Adjust, Scale.
        [ 0.0,  1.0, 1.0],
        [ 0.0,  1.0, 1.0],
        [ 0.0,  1.0, 1.0],
        [ 0.0,  1.0, 1.0]
    ];

    /**
     * Adjustment factor to convert the natural sine duty cycle into quadrants.
     */
    private const ADJUST = 0.5 * M_PI;

    /**
     * @inheritDoc
     */
    public function getPeriod(): float {
        return static::PERIOD;
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
            $fSin        = sin($oRephase[$i]); // @phpstan-ignore-line - false positive
            $iQuadrant   = ((int)$fPhase) & 3;
            $oOutput[$i] = ($fSin * $aTransform[$iQuadrant][2]) + $aTransform[$iQuadrant][1];
        }
        return $oOutput;
    }

    public function value(float $fInput): float {
        $fPhase     = $fInput;
        $iQuadrant  = ((int)$fPhase) & 3;
        $aTransform = static::TRANSFORM[$iQuadrant];
        $fPhase     += $aTransform[0];
        $fPhase     *= self::ADJUST;
        $fSin       = sin($fPhase);
        return ($fSin * $aTransform[2]) + $aTransform[1];
    }
}
