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

/**
 * WhiteNoise
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class WhiteNoise implements Audio\Signal\IWaveform {

    const
        F_PERIOD   = 1.0,
        RAND_SCALE = 1.0/65536.0
    ;

    /**
     * Shared buffer of random values.
     */
    private static ?Audio\Signal\Packet $oRandom = null;
    private static float $fNormalise = 0.0;

    public function __construct() {
        if (null === self::$oRandom) {
            self::$fNormalise = 2.0 / (float)mt_getrandmax();
            self::$oRandom = Audio\Signal\Packet::create();
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                self::$oRandom[$i] = mt_rand();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getPeriod() : float {
        return self::F_PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Audio\Signal\Packet $oInput) : Audio\Signal\Packet {
        $fRandom = self::RAND_SCALE * mt_rand();
        $oOutput = clone $oInput;
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            // Update the random buffer and output buffer as we go
            self::$oRandom[$i] = $iRandom = (self::$oRandom[$i] * $fRandom) & 0x7FFFFFFF;
            $oOutput[$i] = ($iRandom * self::$fNormalise) - 1.0;
        }
        return $oOutput;
    }
}
