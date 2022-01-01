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
 * Shaper
 */
class Shaper implements Audio\Signal\IWaveform {

    const
        PERIOD   = 4.0,
        MUTATION = [
            // quadrant/bias/scale
            [ 3.0,  1.0, 1.0],
            [ 1.0,  1.0, 1.0],
            [-1.0, -1.0, 1.0],
            [-3.0, -1.0, 1.0]
        ];

    const
        UNCHANGED               = 0, // No effect on input waveform

        // Basic rectification
        RECTIFIED_POS_HALF      = 1, // Positive values retained, negative values zeroed
        RECTIFIED_POS_HALF_NORM = 2, // Positive values retained, negative values zeroed, normalised
        RECTIFIED_POS_FULL      = 3, // Positive values retained, negative values flipped
        RECTIFIED_POS_FULL_NORM = 4,
        RECTIFIED_NEG_HALF      = 5, // Positive values retained, negative values zeroed
        RECTIFIED_NEG_HALF_NORM = 6, // Positive values retained, negative values zeroed, normalised
        RECTIFIED_NEG_FULL      = 7, // Positive values retained, negative values flipped
        RECTIFIED_NEG_FULL_NORM = 8,

        // Other configurations
        PINCH = 9
    ;



    private Audio\Signal\IWaveform $oSource;

    /** @var float[][] $aTransform */
    private array $aTransform;
    private float $fPeriodAdjust;

    private float
        $fPrev1 = 0.0,
        $fPrev2 = 0.0,
        $fPrev3 = 0.0,
        $fPrev4 = 0.0
    ;

    /**
     * Constructor
     *
     * @param Audio\Signal\IWaveform $oSource
     */
    public function __construct(Audio\Signal\IWaveform $oSource) {
        $this->oSource       = $oSource;
        $this->aTransform       = self::STANDARD_VARIANTS[self::UNCHANGED];
        $this->fPeriodAdjust = $oSource->getPeriod() / self::PERIOD;
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

        $oAdjust = clone $oInput;

        // Mutate the quadrant phase
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase      = $oInput[$i];
            $iQuadrant   = ((int)$fPhase) & 3;
            $fPhase      += $this->aTransform[$iQuadrant][0];
            $fPhase      *= $this->fPeriodAdjust;
            $oAdjust[$i] = $fPhase;
        }

        $oOutput = $this->oSource->map($oAdjust);

        $fPrev1  = $this->fPrev1;
        $fPrev2  = $this->fPrev2;
        $fPrev3  = $this->fPrev3;
        $fPrev4  = $this->fPrev4;

        // Mutate the quadrant bias
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase    = $oInput[$i];
            $iQuadrant = ((int)$fPhase) & 3;
            $fSample   = ($oOutput[$i] * $this->aTransform[$iQuadrant][2]) + $this->aTransform[$iQuadrant][1];
            $oOutput[$i] = 0.1 * (
                $fSample + $fPrev4 +
                2.0 * ($fPrev1 + $fPrev3)
                + 4.0 * $fPrev2
            );
            $fPrev4 = $fPrev3;
            $fPrev3 = $fPrev2;
            $fPrev2 = $fPrev1;
            $fPrev1 = $fSample;
        }

        return $oOutput;
    }

    /**
     * @const array<int, float[][]>
     */
    private const STANDARD_VARIANTS = [
        self::UNCHANGED => [
            [ 0.0,  0.0,  1.0],
            [ 1.0,  0.0,  1.0],
            [ 2.0,  0.0,  1.0],
            [ 3.0,  0.0,  1.0]
        ],
        self::RECTIFIED_POS_HALF => [
            [ 0.0,  0.0,  1.0],
            [ 1.0,  0.0,  1.0],
            [ 2.0,  0.0,  0.0],
            [ 3.0,  0.0,  0.0]
        ],
        self::RECTIFIED_POS_HALF_NORM => [
            [ 0.0, -1.0,  2.0],
            [ 1.0, -1.0,  2.0],
            [ 2.0, -1.0,  0.0],
            [ 3.0, -1.0,  0.0]
        ],
        self::RECTIFIED_POS_FULL => [
            [ 0.0,  0.0,  1.0],
            [ 1.0,  0.0,  1.0],
            [ 2.0,  0.0, -1.0],
            [ 3.0,  0.0, -1.0]
        ],
        self::RECTIFIED_POS_FULL_NORM => [
            [ 0.0, -1.0,  2.0],
            [ 1.0, -1.0,  2.0],
            [ 2.0, -1.0, -2.0],
            [ 3.0, -1.0, -2.0]
        ],
        self::RECTIFIED_NEG_HALF => [
            [ 0.0,  0.0,  0.0],
            [ 1.0,  0.0,  0.0],
            [ 2.0,  0.0,  1.0],
            [ 3.0,  0.0,  1.0]
        ],
        self::RECTIFIED_NEG_HALF_NORM => [
            [ 0.0,  1.0,  0.0],
            [ 1.0,  1.0,  0.0],
            [ 2.0,  1.0,  2.0],
            [ 3.0,  1.0,  2.0]
        ],
        self::RECTIFIED_NEG_FULL => [
            [ 0.0,  0.0, -1.0],
            [ 1.0,  0.0, -1.0],
            [ 2.0,  0.0,  1.0],
            [ 3.0,  0.0,  1.0]
        ],
        self::RECTIFIED_NEG_FULL_NORM => [
            [ 0.0,  1.0, -2.0],
            [ 1.0,  1.0, -2.0],
            [ 2.0,  1.0,  2.0],
            [ 3.0,  1.0,  2.0]
        ],

        self::PINCH => [
            [ 3.0,  1.0, 1.0],
            [ 1.0,  1.0, 1.0],
            [-1.0, -1.0, 1.0],
            [-3.0, -1.0, 1.0]
        ]
    ];
}
