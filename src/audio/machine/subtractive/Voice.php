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

namespace ABadCafe\PDE\Audio\Machine\Subtractive;
use ABadCafe\PDE\Audio;

use function \min, \max;

/**
 * Voice (ProPHPet)
 *
 * Contains a pair of audio oscillators that can each be set to some multiple of the base frequency.
 * Oscillator 1 can also be set to modulate the phase and/or amplitude of Oscillator 2. The mixed
 * output of the two Oscillators is then routed through an optional filter stage.
 */
class Voice implements Audio\Signal\IStream {

    const
        ID_OSC_1 = 0,
        ID_OSC_2 = 1
    ;

    const
        MIN_RATIO = 1.0/16.0,
        MAX_RATIO = 16.0
    ;

    const
        FILTER_OFF = 0,
        FILTER_LP  = 1,
        FILTER_BP  = 2,
        FILTER_HP  = 3
    ;

    private const OSC_NAMES = [
        self::ID_OSC_1 => 'o1',
        self::ID_OSC_2 => 'o2'
    ];

    private Audio\Signal\FixedMixer       $oMixer;
    private Audio\Signal\Modulator        $oRingModulator;

    /** @var Audio\Signal\AutoMuteSilence<Audio\Signal\IStream> $oOutput */
    private Audio\Signal\AutoMuteSilence  $oOutput;

    // Filter stuff
    private Audio\Signal\Filter\LowPass   $oLowPassFilter;
    private Audio\Signal\Filter\BandPass  $oBandPassFilter;
    private Audio\Signal\Filter\HighPass  $oHighPassFilter;
    private ?Audio\Signal\Oscillator\LFO  $oCutoffLFO         = null;
    private ?Audio\Signal\IEnvelope       $oCutoffEnvelope    = null;
    private ?Audio\Signal\Oscillator\LFO  $oResonanceLFO      = null;
    private ?Audio\Signal\IEnvelope       $oResonanceEnvelope = null;


    /** @var array<int, Audio\Signal\Oscillator\Sound> $aOscillator */
    private array $aOscillator;

    /** @var array<int, float> $aFreqRatio */
    private array $aFreqRatio;

    /** @var array<int, Audio\Signal\IStream> $aOutputTap */
    private array $aOutputTap;

    private float $fBaseFrequency    = 440.0;

    /**
     * Constructor
     *
     * Default configuration is for a 50/50 mix of octave separated saw waves and a slight
     * detune.
     */
    public function __construct() {
        $this->oMixer = new Audio\Signal\FixedMixer();
        $this->aFreqRatio = [
            self::ID_OSC_1 => 1.002,
            self::ID_OSC_2 => 0.499
        ];

        $oInitWave = Audio\Signal\Waveform\Flyweight::get()->getWaveform(
            Audio\Signal\IWaveform::SAW
        );

        $this->aOscillator = [];
        foreach ($this->aFreqRatio as $iOsc => $fFreqRatio) {
            $this->aOscillator[$iOsc] = new Audio\Signal\Oscillator\Sound(
                $oInitWave,
                $this->fBaseFrequency * $fFreqRatio
            );
            $this->oMixer->addInputStream(
                self::OSC_NAMES[$iOsc],
                $this->aOscillator[$iOsc],
                0.5
            );
        }

        $this->oRingModulator = new Audio\Signal\Modulator(
            $this->aOscillator[self::ID_OSC_1],
            $this->aOscillator[self::ID_OSC_2]
        );

        $this->oRingModulator->disable();

        // Only one will be active at once
        $this->oLowPassFilter  = new Audio\Signal\Filter\LowPass($this->oMixer);
        $this->oBandPassFilter = new Audio\Signal\Filter\BandPass($this->oMixer);
        $this->oHighPassFilter = new Audio\Signal\Filter\HighPass($this->oMixer);

        $this->aOutputTap = [
            self::FILTER_OFF => $this->oMixer,
            self::FILTER_LP  => $this->oLowPassFilter,
            self::FILTER_BP  => $this->oBandPassFilter,
            self::FILTER_HP  => $this->oHighPassFilter
        ];
        // Filter off by default

        /** @var Audio\Signal\IStream $oStream */
        $oStream = $this->oMixer;
        $this->oOutput = new Audio\Signal\AutoMuteSilence($oStream, 0.05, 1.0/512.0);
        $this->oOutput->disable();
    }

    /**
     * Set the baseline frequency to emit.
     *
     * @param  float $fFrequency
     * @return self
     */
    public function setFrequency(float $fFrequency): self {
        $this->fBaseFrequency = $fFrequency;
        foreach ($this->aOscillator as $iOsc => $oOscillator) {
            $oOscillator->setFrequency($this->fBaseFrequency * $this->aFreqRatio[$iOsc]);
        }
        return $this;
    }

    /**
     * Set the frequency ratio for an oscillator. Value is clamped. Out of range oscillators
     * are ignored.
     *
     * @param  int   $iOsc - which oscillator
     * @param  float $fRatio
     * @return self
     */
    public function setFrequencyRatio(int $iOsc, float $fRatio): self {
        if (isset($this->aOscillator[$iOsc])) {
            $fRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
            $this->aFreqRatio[$iOsc] = $fRatio;
            $this->aOscillator[$iOsc]->setFrequency($this->fBaseFrequency * $fRatio);
        }
        return $this;
    }


    /**
     * Set the output mix level for an oscillator. Out of range oscillators are ignored.
     */
    public function setMixLevel(int $iOsc, float $fLevel): self {
        if (isset(self::OSC_NAMES[$iOsc])) {
            $this->oMixer->setInputLevel(self::OSC_NAMES[$iOsc], $fLevel);
        }
        return $this;
    }

    public function setWaveform(int $iOsc, Audio\Signal\IWaveform $oWaveform): self {
        if (isset($this->aOscillator[$iOsc])) {
            $this->aOscillator[$iOsc]->setWaveform($oWaveform);
        }
        return $this;
    }

    public function setLevelEnvelope(int $iOsc, ?Audio\Signal\IEnvelope $oEnvelope): self {
        if (isset($this->aOscillator[$iOsc])) {
            $oEnvelope = $oEnvelope ? clone $oEnvelope : null;
            $this->aOscillator[$iOsc]->setLevelEnvelope($oEnvelope);
        }
        return $this;
    }

    public function setLevelLFO(int $iOsc, ?Audio\Signal\Oscillator\LFO $oLFO): self {
        if (isset($this->aOscillator[$iOsc])) {
            $this->aOscillator[$iOsc]->setLevelModulator($oLFO);
        }
        return $this;
    }

    public function setPitchEnvelope(int $iOsc, ?Audio\Signal\IEnvelope $oEnvelope): self {
        if (isset($this->aOscillator[$iOsc])) {
            $oEnvelope = $oEnvelope ? clone $oEnvelope : null;
            $this->aOscillator[$iOsc]->setPitchEnvelope($oEnvelope);
        }
        return $this;
    }

    public function setPitchLFO(int $iOsc, ?Audio\Signal\Oscillator\LFO $oLFO): self {
        if (isset($this->aOscillator[$iOsc])) {
            $this->aOscillator[$iOsc]->setPitchModulator($oLFO);
        }
        return $this;
    }

    /**
     * Set the level of phase modulatiom that oscillator 1 applies to oscillator 2. If the level is
     * set at or below zero, oscillator 1 is disconnected as a phase modulator.
     */
    public function setPhaseModulationIndex(float $fIndex): self {
        if ($fIndex <= 0.0) {
            $this->aOscillator[self::ID_OSC_2]->setPhaseModulator(null);
            $this->aOscillator[self::ID_OSC_2]->setPhaseModulationIndex(0.0);
        } else {
            $this->aOscillator[self::ID_OSC_2]->setPhaseModulator(
                $this->aOscillator[self::ID_OSC_1]
            );
            $this->aOscillator[self::ID_OSC_2]->setPhaseModulationIndex($fIndex);
        }
        return $this;
    }

    public function setRingModulationIndex(float $fIndex): self {
        if ($fIndex <= 0.0) {
            $this->oRingModulator->disable();
            $this->oMixer->removeInputStream('rm');
        } else {
            $this->oRingModulator->enable();
            $this->oMixer->addInputStream('rm', $this->oRingModulator, $fIndex);
        }
        return $this;
    }

    /**
     * Set the filter mode to use. This works by selecting the appropriate filter to tap as the
     * output. If the filter mode is for no filter, the direct output of the oscillator summing
     * mixer is tapped instead.
     */
    public function setFilterMode(int $iFilterMode): self {
        if (isset($this->aOutputTap[$iFilterMode])) {
            $this->oOutput->setStream($this->aOutputTap[$iFilterMode]);
        }
        return $this;
    }

    /**
     * Set the cutoff level of the filter. This is applied to all filters so that if the mode
     * is changed, the same cutoff value is in effect.
     */
    public function setFilterCutoff(float $fCutoff): self {
        $this->oLowPassFilter->setCutoff($fCutoff);
        $this->oBandPassFilter->setCutoff($fCutoff);
        $this->oHighPassFilter->setCutoff($fCutoff);
        return $this;
    }

    /**
     * Set the cutoff LFO to use. If the LFO is used in conjunction with an envelope, these are
     * multiplicitavely combined.
     */
    public function setFilterCutoffLFO(?Audio\Signal\Oscillator\LFO $oLFO): self {
        // If the LFO has changed only...
        if ($oLFO !== $this->oCutoffLFO) {
            $this->oCutoffLFO = $oLFO;
            if (!$this->oCutoffEnvelope) {
                // If there is no envelope, apply only the LFO
                $this->setFilterCutoffControl($this->oCutoffLFO);
            } else if ($this->oCutoffLFO) {
                // If there is an envelope, pre-modulate the LFO and envelope
                $this->setFilterCutoffControl(
                    new Audio\Signal\Modulator(
                        $this->oCutoffLFO,
                        $this->oCutoffEnvelope
                    )
                );
            } else {
                // There are no controls
                $this->setFilterCutoffControl(null);
            }
        }
        return $this;
    }

    /**
     * Set the cutoff envelope to use. If the envelope is used in conjunction with an LFO, these are
     * multiplicitavely combined.
     */
    public function setFilterCutoffEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        // If the envelope has changed only...
        if ($oEnvelope !== $this->oCutoffEnvelope) {
            $this->oCutoffEnvelope = $oEnvelope ? clone $oEnvelope : null;
            if (!$this->oCutoffLFO) {
                // If there is no LFO, apply only the envelope
                $this->setFilterCutoffControl($this->oCutoffEnvelope);
            } else if ($this->oCutoffEnvelope) {
                // If there is an LFO, pre-modulate the LFO and envelope
                $this->setFilterCutoffControl(
                    new Audio\Signal\Modulator(
                        $this->oCutoffLFO,
                        $this->oCutoffEnvelope
                    )
                );
            } else {
                // There are no controls
                $this->setFilterCutoffControl(null);
            }
        }
        return $this;
    }

    /**
     * Set the cutoff level of the filter. This is applied to all filters so that if the mode
     * is changed, the same cutoff value is in effect.
     */
    public function setFilterResonance(float $fResonance): self {
        $this->oLowPassFilter->setResonance($fResonance);
        $this->oBandPassFilter->setResonance($fResonance);
        $this->oHighPassFilter->setResonance($fResonance);
        return $this;
    }

    /**
     * Set the resonance LFO to use. If the LFO is used in conjunction with an envelope, these are
     * multiplicitavely combined.
     */
    public function setFilterResonanceLFO(?Audio\Signal\Oscillator\LFO $oLFO): self {
        // If the LFO has changed only...
        if ($oLFO !== $this->oResonanceLFO) {
            $this->oResonanceLFO = $oLFO;
            if (!$this->oResonanceEnvelope) {
                // If there is no envelope, apply only the LFO
                $this->setFilterResonanceControl($this->oCutoffLFO);
            } else if ($this->oResonanceLFO) {
                // If there is an envelope, pre-modulate the LFO and envelope
                $this->setFilterResonanceControl(
                    new Audio\Signal\Modulator(
                        $this->oResonanceLFO,
                        $this->oResonanceEnvelope
                    )
                );
            } else {
                // There are no controls
                $this->setFilterCutoffControl(null);
            }
        }
        return $this;
    }

    /**
     * Set the resonance envelope to use. If the envelope is used in conjunction with an LFO, these are
     * multiplicitavely combined.
     */
    public function setFilterResonanceEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        // If the envelope has changed only...
        if ($oEnvelope !== $this->oResonanceEnvelope) {
            $this->oResonanceEnvelope = $oEnvelope ? clone $oEnvelope : null;
            if (!$this->oResonanceLFO) {
                // If there is no LFO, apply only the envelope
                $this->setFilterResonanceControl($this->oResonanceEnvelope);
            } else if ($this->oResonanceEnvelope) {
                // If there is an LFO, pre-modulate the LFO and envelope
                $this->setFilterResonanceControl(
                    new Audio\Signal\Modulator(
                        $this->oResonanceLFO,
                        $this->oResonanceEnvelope
                    )
                );
            } else {
                // There are no controls
                $this->setFilterResonanceControl(null);
            }
        }
        return $this;
    }


    /**
     * Enable a stream.
     *
     * @return self
     */
    public function enable(): self {
        $this->oOutput->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disable(): self {
        $this->oOutput->disable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool {
        return $this->oOutput->isEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->oOutput->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->aOscillator[self::ID_OSC_1]->reset();
        $this->aOscillator[self::ID_OSC_2]->reset();
        $this->oLowPassFilter->reset();
        $this->oBandPassFilter->reset();
        $this->oHighPassFilter->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        return $this->oOutput->emit($iIndex);
    }

    private function setFilterCutoffControl(?Audio\Signal\IStream $oControl): void {
        $this->oLowPassFilter->setCutoffControl($oControl);
        $this->oBandPassFilter->setCutoffControl($oControl);
        $this->oHighPassFilter->setCutoffControl($oControl);
    }

    private function setFilterResonanceControl(?Audio\Signal\IStream $oControl): void {
        $this->oLowPassFilter->setResonanceControl($oControl);
        $this->oBandPassFilter->setResonanceControl($oControl);
        $this->oHighPassFilter->setResonanceControl($oControl);
    }

}
