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
use ABadCafe\PDE\Audio\Signal\IWaveform;


/**
 * Flyweight
 *
 * Holds the set of IWaveform implementation instances for reuse. Has an understanding of which implementations
 * are stateless and which are not.
 */
class Flyweight {

    /**
     * @const array<int, class-string>
     */
    private const WAVE_TYPES = [
        IWaveform::SINE               => Sine::class,
        IWaveform::SINE_HALF_RECT     => SineHalfRect::class,
        IWaveform::SINE_FULL_RECT     => SineFullRect::class,
        IWaveform::SINE_SAW           => SineSaw::class,
        IWaveform::SINE_PINCH         => SinePinch::class,
        IWaveform::SINE_CUT           => SineCut::class,
        IWaveform::TRIANGLE           => Triangle::class,
        IWaveform::TRIANGLE_HALF_RECT => TriangleHalfRect::class,
        IWaveform::SAW                => Saw::class,
        //IWaveform::SAW_HALF_RECT      => SawHalfRect::class,
        IWaveform::SAW_ALIASED        => AliasedSaw::class,
        IWaveform::SQUARE             => Square::class,
        IWaveform::SQUARE_ALIASED     => AliasedSquare::class,
        IWaveform::PULSE              => Pulse::class,
        IWaveform::PULSE_ALIASED      => AliasedPulse::class,
        IWaveform::NOISE              => WhiteNoise::class,
    ];

    /**
     * @const array<int, bool>
     */
    private const CLONE_ALWAYS = [
        IWaveform::SAW           => true, // hamming window state
        IWaveform::SQUARE        => true, // hamming window state
        IWaveform::PULSE         => true, // hamming window and pulse width state
        IWaveform::PULSE_ALIASED => true  // pulse width state
    ];

    private static ?self $oInstance = null;

    /**
     * @var array<int, IWaveform> $aWaveforms
     */
    private array $aWaveforms = [];

    /**
     * Singleton
     */
    public static function get(): self {
        if (null === self::$oInstance) {
            self::$oInstance = new self;
        }
        return self::$oInstance;
    }

    /**
     * Get a waveform by enumeration. Throws \OutOfBoundsException if the enumeration is not valid.
     *
     * @param  int $iEnum
     * @return IWaveform
     * @throws \OutOfBoundsException
     */
    public function getWaveform(int $iEnum): IWaveform {
        if (!isset($this->aWaveforms[$iEnum])) {
            throw new \OutOfBoundsException('Unknown Waveform Enumeration #' . $iEnum);
        }
        return isset(self::CLONE_ALWAYS[$iEnum]) ?
            clone $this->aWaveforms[$iEnum] :
            $this->aWaveforms[$iEnum];
    }

    /**
     * Get a set of IWaveform instances for a given input set of enumerations.
     *
     * @param  int[] $aWaveformEnum
     * @return array<int, IWaveform>
     * @throws \OutOfBoundsException
     */
    public function getWaveforms(array $aWaveformEnum): array {
        $aResult = [];
        foreach ($aWaveformEnum as $iEnum) {
            if (!isset($this->aWaveforms[$iEnum])) {
                throw new \OutOfBoundsException('Unknown Waveform Enumeration #' . $iEnum);
            }
            if (
                isset(self::CLONE_ALWAYS[$iEnum])
            ) {
                $aResult[$iEnum] = clone $this->aWaveforms[$iEnum];
            } else {
                $aResult[$iEnum] = $this->aWaveforms[$iEnum];
            }
        }
        return $aResult;
    }

    private function __construct() {
        foreach (self::WAVE_TYPES as $iEnum => $sClass) {
            $this->aWaveforms[$iEnum] = new $sClass;
        }
    }
}
