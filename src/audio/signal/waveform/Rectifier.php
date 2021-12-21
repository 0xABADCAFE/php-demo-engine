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

/**
 * Rectifier
 *
 * Transforms the output of an existing waveform by applying limits allowing for rectification, etc.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Rectifier implements Signal\IWaveform {

    const
        NONE             = 0,
        HALF_RECT_P      = 1, // Half wave rectification, positive half (0.0 ... 1.0)
        HALF_RECT_N      = 2, // Half wave rectification, negative half (-1.0 ... 0.0)
        HALF_RECT_P_FS   = 3, // Half wave rectification, positive half, scaled (-1.0 ... 1.0)
        HALF_RECT_N_FS   = 4, // Half wave rectification, negative half, scaled (-1.0 ... 1.0)
        FULL_RECT_P      = 5, // Full wave rectification, positive half (0.0 ... 1.0)
        FULL_RECT_N      = 6, // Full wave rectification, negative half (-1.0 ... 0.0)
        FULL_RECT_P_FS   = 7, // Full wave rectification, positive half, scaled (-1.0 ... 1.0)
        FULL_RECT_N_FS   = 8  // Full wave rectification, negative half, scaled (-1.0 ... 1.0)
    ;

    private Signal\IWaveform $oSource;
    private float            $fMin, $fMax, $fBias, $fScale;
    private bool             $bFold;

    /**
     * Create one of the standard enumerated rectifier configurations above,
     *
     * @param  Signal\IWaveform $oSource
     * @param  int $iModifier
     * @return self
     */
    public static function createStandard(Signal\IWaveform $oSource, int $iModifier): Signal\IWaveform {
        switch ($iModifier) {
            case self::HALF_RECT_P:
                return new self(
                    $oSource,
                    0.0,      // Minimum
                    1.0,      // Maximum
                    false,    // Fold
                    1.0,      // Scale
                    0.0       // Bias
                );

            case self::HALF_RECT_N:
                return new self(
                    $oSource,
                    -1.0,     // Minimum
                    0.0,      // Maximum
                    false,    // Fold
                    1.0,      // Scale
                    0.0       // Bias
                );

            case self::HALF_RECT_P_FS:
                return new self(
                    $oSource,
                    0.0,      // Minimum
                    1.0,      // Maximum
                    false,    // Fold
                    2.0,      // Scale
                    -1.0      // Bias
                );

            case self::HALF_RECT_N_FS:
                return new self(
                    $oSource,
                    -1.0,     // Minimum
                    0.0,      // Maximum
                    false,    // Fold
                    2.0,      // Scale
                    1.0       // Bias
                );

            case self::FULL_RECT_P:
                return new self(
                    $oSource,
                    0.0,      // Minimum
                    1.0,      // Maximum
                    true,     // Fold
                    1.0,      // Scale
                    0.0       // Bias
                );

            case self::FULL_RECT_N:
                return new self(
                    $oSource,
                    -1.0,     // Minimum
                    0.0,      // Maximum
                    true,     // Fold
                    1.0,      // Scale
                    0.0       // Bias
                );

            case self::FULL_RECT_P_FS:
                return new self(
                    $oSource,
                    0.0,      // Minimum
                    1.0,      // Maximum
                    true,     // Fold
                    2.0,      // Scale
                    -1.0      // Bias
                );

            case self::FULL_RECT_N_FS:
                return new self(
                    $oSource,
                    -1.0,     // Minimum
                    0.0,      // Maximum
                    true,     // Fold
                    2.0,      // Scale
                    1.0       // Bias
                );

            default:
                // No modification
                return $oSource;
        }
    }

    /**
     * Constructor
     *
     * @param Signal\IWaveform $oSource - Initial waveform to rectify
     * @param float            $fMin    - Low threshold for rectification
     * @param float            $fMax    - High threshold for rectification
     * @param bold             $bFold   - Whether or not the waveform should be folded back at the rectification limit
     * @param float            $fScale  - How much to scale the output by
     * @param float            fBias    - How much to offset the output by
     */
    public function __construct(
        Signal\IWaveform $oSource,
        float $fMin,
        float $fMax,
        bool  $bFold  = false,
        float $fScale = 1.0,
        float $fBias  = 0.0
    ) {
        $this->oSource = clone $oSource;
        $this->fMin    = $fMin;
        $this->fMax    = $fMax;
        $this->fBias   = $fBias;
        $this->fScale  = $fScale;
        $this->bFold   = $bFold;
    }

    /**
     * @inheritDoc
     */
    public function getPeriod(): float {
        return $this->oSource->getPeriod();
    }

    /**
     * @inheritDoc
     */
    public function map(Signal\Packet $oInput): Signal\Packet {
        $oOutput = $this->oSource->map($oInput);
        $fMin    = $this->fMin;
        $fMax    = $this->fMax;
        if ($this->bFold) {
            $fMin2 = $fMin * 2.0;
            $fMax2 = $fMax * 2.0;
            foreach ($oOutput as $i => $fValue) {
                $fValue > $fMax && $fValue = $fMax2 - $fValue;
                $fValue < $fMin && $fValue = $fMin2 - $fValue;
                $oOutput[$i] = $this->fScale * $fValue + $this->fBias;
            }
        } else {
            foreach ($oOutput as $i => $fValue) {
                $fValue > $fMax && $fValue = $fMax;
                $fValue < $fMin && $fValue = $fMin;
                $oOutput[$i] = $this->fScale * $fValue + $this->fBias;
            }
        }
        return $oOutput;
    }
}
