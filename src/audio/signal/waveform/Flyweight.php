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
     * @const array<string, int>
     */
    const WAVE_NAME_MAP = [
        'Sine'        => IWaveform::SINE,
        'SineHR'      => IWaveform::SINE_HALF_RECT,
        'SineFR'      => IWaveform::SINE_FULL_RECT,
        'SineSaw'     => IWaveform::SINE_SAW,
        'SinePinch'   => IWaveform::SINE_PINCH,
        'SineCut'     => IWaveform::SINE_CUT,
        'SineSawHard' => IWaveform::SINE_SAW_HARD,
        'Triangle'    => IWaveform::TRIANGLE,
        'TriangleHR'  => IWaveform::TRIANGLE_HALF_RECT,
        'Saw'         => IWaveform::SAW,
        'SawAliased'  => IWaveform::SAW_ALIASED,
        'Square'      => IWaveform::SQUARE,
        'Pokey'       => IWaveform::POKEY,
        'Pulse'       => IWaveform::PULSE,
        'Noise'       => IWaveform::NOISE
    ];

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
        IWaveform::SINE_SAW_HARD      => SineSawHard::class,
        IWaveform::TRIANGLE           => Triangle::class,
        IWaveform::TRIANGLE_HALF_RECT => TriangleHalfRect::class,
        IWaveform::SAW                => Saw::class,
        IWaveform::SAW_ALIASED        => AliasedSaw::class,
        IWaveform::SQUARE             => Square::class,
        IWaveform::SQUARE_ALIASED     => AliasedSquare::class,
        IWaveform::POKEY              => Pokey::class,
        IWaveform::PULSE              => Pulse::class,
        IWaveform::PULSE_ALIASED      => AliasedPulse::class,
        IWaveform::NOISE              => WhiteNoise::class,
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
        return $this->aWaveforms[$iEnum]->share();
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
            $aResult[$iEnum] = $this->aWaveforms[$iEnum]->share();
        }
        return $aResult;
    }

    private function __construct() {
        foreach (self::WAVE_TYPES as $iEnum => $sClass) {
            $this->aWaveforms[$iEnum] = new $sClass;
        }
    }
}
