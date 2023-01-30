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
use ABadCafe\PDE\Util;
use \SPLFixedArray;
use function \mt_getrandmax, \mt_rand;

/**
 * WhiteNoise
 *
 * Uses one mt_rand() call per packet to generate a stream of (pseudo) white noise.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class WhiteNoise implements Audio\Signal\IWaveform {

    use Util\TAlwaysShareable;

    const
        /**
         * Waveform period (interval after which it repeats). This is technically meaningless for noise.
         */
        PERIOD     = 1.0,

        /**
         * Generation of new random values goes through a floating point conversion step.
         */
        RAND_SCALE = 1.0/65536.0
    ;

    /**
     * @var \SPLFixedArray<int> Shared buffer of random values.
     */
    private static \SPLFixedArray $oRandom;
    private static float $fNormalise = 0.0;

    /**
     * Constructor
     */
    public function __construct() {
        if (!self::$fNormalise) {
            self::$fNormalise = 2.0 / (float)mt_getrandmax();
            self::$oRandom = new \SPLFixedArray(Audio\IConfig::PACKET_SIZE);
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                self::$oRandom[$i] = mt_rand();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getPeriod(): float {
        return self::PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Audio\Signal\Packet $oInput): Audio\Signal\Packet {
        $fRandom = self::RAND_SCALE * mt_rand();
        $iMask   = 0x7FFFFFFF;
        $oOutput = clone $oInput;
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            // Update the random buffer and output buffer as we go
            self::$oRandom[$i] = $iRandom = (int)(self::$oRandom[$i] * $fRandom) & $iMask;
            $oOutput[$i] = ($iRandom * self::$fNormalise) - 1.0;
        }
        return $oOutput;
    }

    /**
     * @inheritDoc
     *
     * @todo - realise this in a non sucky way.
     */
    public function value(float $fInput): float {
        return 0.0;
    }
}
