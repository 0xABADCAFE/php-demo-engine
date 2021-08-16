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
 * AliasedPulse
 *
 * PWM implementation of IWaveform. Does not attempt to peform anti aliasing on the output. Faster for low frequencies
 * and interestingly noisy for higher frequencies.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class AliasedPulse implements Signal\IWaveform {

    const
        /**
         * Waveform period (interval after which it repeats).
         */
        PERIOD    = 1.0,
        MIN_WIDTH = 1.0/16.0,
        MAX_WIDTH = 15.0/16.0,
        DEF_WIDTH = 0.25
    ;

    protected float $fPulseWidth = 0.25;

    /**
     * Optional modulator for pulsewidth.
     */
    protected ?Signal\IStream $oModulator = null;

    /**
     * Constructor.
     *
     * @param float               $fPulseWidth - how far into the duty cycle the switch happens.
     * @param Signal\IStream|null $oModulator  - optional modulator for pulse width
     */
    public function __construct(float $fPulseWidth = self::DEF_WIDTH, ?Signal\IStream $oModulator = null) {
        $this->setPulsewidth($fPulseWidth);
        $this->setPulsewidthModulator($oModulator);
    }

    /**
     * Set the pulse width. Clamps to MIN_WIDTH/MAX_WIDTH
     *
     * @param  float $fPulseWidth
     * @return self
     */
    public function setPulsewidth(float $fPulseWidth) : self {
        $fPulseWidth > self::MAX_WIDTH && $fPulseWidth = self::MAX_WIDTH;
        $fPulseWidth < self::MIN_WIDTH && $fPulseWidth = self::MIN_WIDTH;
        $this->fPulseWidth = $fPulseWidth;
        return $this;
    }

    /**
     * Optional pulse width modulator to use. Note that this creates free-running clone of the input.
     *
     * @param  Signal\IStream|null $oModulator
     * @return self
     */
    public function setPulsewidthModulator(?Signal\IStream $oModulator) : self {
        if ($oModulator) {
            $this->oWidthModulator = clone $oModulator;
        } else {
            $this->oWidthModulator = null;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPeriod() : float {
        return self::PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Signal\Packet $oInput) : Signal\Packet {
        $oOutput = clone $oInput;
        if ($this->oWidthModulator) {
            $oWidth = $this->oWidthModulator
                ->emit()
                ->scaleBy(0.5 * $this->fPulseWidth)
                ->biasBy(0.5);
            foreach ($oInput as $i => $fTime) {
                $oOutput[$i] = ((\ceil($fTime) - $fTime) > $oWidth[$i]) ? 1.0 : -1.0;
            }
        } else {
            foreach ($oInput as $i => $fTime) {
                $oOutput[$i] = ((\ceil($fTime) - $fTime) > $this->fPulseWidth) ? 1.0 : -1.0;
            }
        }
        return $oOutput;
    }
}
