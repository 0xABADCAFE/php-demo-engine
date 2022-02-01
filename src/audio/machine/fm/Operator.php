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

namespace ABadCafe\PDE\Audio\Machine\FM;
use ABadCafe\PDE\Audio;
use function \max, \min;

/**
 * Operator
 *
 * Oscillator implementation that provides the fundamentals for an FM Operator:
 *
 *    Waveform          : Injectable dependency
 *    Level Envelope    : Injectable dependency
 *    Pitch Envelope    : Injectable dependency
 *    Level LFO         : Fixed dependency, injectable waveform
 *    Pitch LFO         : Fixed dependency, injectable waveform
 *    Modulator Inputs  : Zero to many other Operator instances
 *    Velocity Dynamics : Injectable dependencies
 *    KeyScale Dynamics : Injectable dependencies
 *
 * The only restriction on modulator input is that an Operator cannot modulate itself, either directly or indirectly.
 * Otherwise, any topology is legal.
 */
class Operator implements Audio\Signal\IOscillator {

    const
        MIN_RATIO  = 1.0/32.0,
        DEF_RATIO  = 1.0,
        MAX_RATIO  = 32.0
    ;

    /**
     * Each operator instance gets a unique runtime ID. This is used to detect illegal circular modulation paths.
     */
    private static int $iNextID   = 0;
    private string     $sUniqueID = '';

    /**
     * The core of the operator is a basic Oscillator that runs at some fixed ratio of the fundamental frequency.
     */
    private Audio\Signal\Oscillator\Sound $oOscillator;
    private float $fRatio = self::DEF_RATIO;

    /**
     * Operator modulation inputs are summed using a fixed mixer, where the level of each input corresponds to that
     * modulator's modulation index.
     */
    private Audio\Signal\Operator\FixedMixer  $oModulation;

    // LFO have injectable waveforms but are direct dependencies. The Level LFO in particular must operate in 1-0 range.
    private Audio\Signal\Oscillator\LFO
        $oLevelLFO,
        $oPitchLFO
    ;

    /**
     * Velocity control curves
     */
    private ?Audio\IControlCurve
        $oLevelIntensityVelocityCurve = null,
        $oLevelRateVelocityCurve      = null,
        $oPitchIntensityVelocityCurve = null,
        $oPitchRateVelocityCurve      = null
    ;

    /**
     * Key scale control curves
     */
//     private ?Audio\IControlCurve
//         $oLevelIntensityKeyScaleCurve = null,
//         $oLevelRateKeyScaleCurve      = null,
//         $oPitchIntensityKeyScaleCurve = null,
//         $oPitchRateKeyScaleCurve      = null
//     ;

    /**
     * @var Operator[] $aModulators
     */
    private array $aModulators = [];

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // FUNDAMENTALS

    /**
     * Constructor. Initialised with sine wave for the oscillator and each LFO. LFO are not enabled.
     *
     * @param float $fRatio - multiplier of the basic frequency the Operator runs at.
     */
    public function __construct($fRatio = self::DEF_RATIO) {
        $this->sUniqueID = 'op' . (++self::$iNextID);
        $this->setRatio($fRatio);
        $oDefaultWaveform  = new Audio\Signal\Waveform\Sine();
        $this->oOscillator = new Audio\Signal\Oscillator\Sound($oDefaultWaveform);
        $this->oLevelLFO   = new Audio\Signal\Oscillator\LFOOneToZero($oDefaultWaveform);
        $this->oPitchLFO   = new Audio\Signal\Oscillator\LFO($oDefaultWaveform);
        $this->oModulation = new Audio\Signal\Operator\FixedMixer();
    }

    /**
     * @return string
     */
    public function getUniqueID(): string {
        return $this->sUniqueID;
    }

    /**
     * @inheritDoc
     */
    public function enable(): self {
        $this->oOscillator->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function disable(): self {
        $this->oOscillator->disable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool {
        return $this->oOscillator->isEnabled();
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->oOscillator->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->oOscillator->reset();
        foreach ($this->aModulators as $oModulator) {
            $oModulator->reset();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        return $this->oOscillator->emit($iIndex);
    }

    /**
     * @inheritDoc
     */
    public function setWaveform(?Audio\Signal\IWaveform $oWaveform): self {
        $this->oOscillator->setWaveform($oWaveform);
        // Don't propagate to modulators
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWaveform(): ?Audio\Signal\IWaveform {
        return $this->oOscillator->getWaveform();
    }


    /**
     * @inheritDoc
     */
    public function setFrequency(float $fFrequency): self {
        $this->oOscillator->setFrequency($fFrequency * $this->fRatio);
        // Don't propagate to modulators
        return $this;
    }

    /**
     * Set the frequency ratio of this operator as an absolute, i.e. multiples of the base frequency.
     *
     * @param  float $fRatio
     * @return self
     */
    public function setRatio(float $fRatio): self {
        $this->fRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // LEVEL LFO

    /**
     * Enable the Level LFO.
     *
     * @return self
     */
    public function enableLevelLFO(): self {
        $this->oOscillator->setLevelModulator($this->oLevelLFO);
        return $this;
    }

    /**
     * Disable the Level LFO.
     *
     * @return self
     */
    public function disableLevelLFO(): self {
        $this->oOscillator->setLevelModulator(null);
        return $this;
    }

    /**
     * Set the Level LFO waveform to use. Setting null disables the Level LFO but does not change
     * the assigned waveform.
     *
     * @param  Audio\Signal\IWaveform|null $oWaveform
     * @return self
     */
    public function setLevelLFOWaveform(?Audio\Signal\IWaveform $oWaveform): self {
        if (null == $oWaveform) {
            $this->disableLevelLFO();
        } else {
            $this->oLevelLFO->setWaveform($oWaveform);
        }
        return $this;
    }

    /**
     * Set the Level LFO depth to use. Setting any value <= 0 disables the Level LFO but does not change
     * the assigned depth.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setLevelLFODepth(float $fDepth): self {
        if ($fDepth <= 0.0) {
            $this->disableLevelLFO();
        } else {
            $this->oLevelLFO->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the Level LFO rate in Hz to use. Setting any value <= 0 disables the Level LFO but does not change
     * the assigned rate.
     *
     * @param  float $fRate
     * @return self
     */
    public function setLevelLFORate(float $fRate): self {
        if ($fRate <= 0.0) {
            $this->disableLevelLFO();
        } else {
            $this->oLevelLFO->setFrequency($fRate);
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // PITCH LFO

    /**
     * Enable the Pitch LFO.
     *
     * @return self
     */
    public function enablePitchLFO(): self {
        $this->oOscillator->setPitchModulator($this->oPitchLFO);
        return $this;
    }

    /**
     * Disable the Pitch LFO.
     *
     * @return self
     */
    public function disablePitchLFO(): self {
        $this->oOscillator->setPitchModulator(null);
        return $this;
    }

    /**
     * Set the Pitch LFO waveform to use. Setting null disables the Pitch LFO but does not change
     * the assigned waveform.
     *
     * @param  Audio\Signal\IWaveform|null $oWaveform
     * @return self
     */
    public function setPitchLFOWaveform(?Audio\Signal\IWaveform $oWaveform): self {
        if (null == $oWaveform) {
            $this->disablePitchLFO();
        } else {
            $this->oPitchLFO->setWaveform($oWaveform);
        }
        return $this;
    }

    /**
     * Set the Pitch LFO depth to use. Setting any value <= 0 disables the Pitch LFO but does not change
     * the assigned depth.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setPitchLFODepth(float $fDepth): self {
        if ($fDepth <= 0.0) {
            $this->disablePitchLFO();
        } else {
            $this->oPitchLFO->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the Pitch LFO rate in Hz to use. Setting any value <= 0 disables the Pitch LFO but does not change
     * the assigned rate.
     *
     * @param  float $fRate
     * @return self
     */
    public function setPitchLFORate(float $fRate): self {
        if ($fRate <= 0.0) {
            $this->disablePitchLFO();
        } else {
            $this->oPitchLFO->setFrequency($fRate);
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // ENVELOPES

    /**
     * Set the level envelope to use. Passing null removes any current envelope.
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        $this->oOscillator->setLevelEnvelope($oEnvelope);
        return $this;
    }

    /**
     * Set the pitch envelope to use. Passing null removes any current envelope.
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        $this->oOscillator->setPitchEnvelope($oEnvelope);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // PHASE MODULATION

    /**
     * Set the self-modulation index for this Operator
     */
    public function setFeedbackIndex(float $fFeedback): self {
        $this->oOscillator->setPhaseFeedbackIndex($fFeedback);
        return $this;
    }

    /**
     * The main FM concept.
     *
     * @param  self  $oModulator  - must not be this instance.
     * @param  float $fIndex      - The modulation strength. 1.0 represents one full duty cycle of the oscillator.
     * @return self
     */
    public function addModulator(self $oModulator, float $fIndex): self {
        // This is not how you self modulate
        if ($this === $oModulator) {
            throw new \LogicException('Cyclical operator configuration');
        }
        // If the modulator is already added, just set the level
        if (isset($this->aModulators[$oModulator->sUniqueID])) {
            $this->oModulation->setInputLevel($oModulator->sUniqueID, $fIndex);
        }
        // If the modulator is not added, we need to make sure it doesn't introduce a cyclical topology...
        // This is basically a tree search to see if our ID occurs anywhere.
        $oModulator->check($this->sUniqueID);

        $this->aModulators[$oModulator->sUniqueID] = $oModulator;
        $this->oModulation->addInputStream($oModulator->sUniqueID, $oModulator, $fIndex);

        if (!empty($this->aModulators)) {
            $this->oOscillator->setPhaseModulator($this->oModulation);
        } else {
            $this->oOscillator->setPhaseModulator(null);
        }
        return $this;
    }

    /**
     * Remove a modulator
     *
     * @param  self $oModulator
     * @return self
     */
    public function removeModulator(self $oModulator): self {
        $this->oModulation->removeInputStream($oModulator->sUniqueID);
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // VELOCITY DYNAMICS

    /**
     * Set or clear the velocity intensity curve for the level envelope. Passing null clears the curve.
     *
     * @param  Audio\IControlCurve|null $oCurve
     * @return self
     */
    public function setLevelIntensityVelocityCurve(?Audio\IControlCurve $oCurve): self {
        $this->oLevelIntensityVelocityCurve = $oCurve;
        return $this;
    }

    /**
     * Set or clear the velocity rate curve for the level envelope. Passing null clears the curve.
     *
     * @param  Audio\IControlCurve|null $oCurve
     * @return self
     */
    public function setLevelRateVelocityCurve(?Audio\IControlCurve $oCurve): self {
        $this->oLevelRateVelocityCurve = $oCurve;
        return $this;
    }

    /**
     * Set or clear the velocity intensity curve for the pitch envelope. Passing null clears the curve.
     *
     * @param  Audio\IControlCurve|null $oCurve
     * @return self
     */
    public function setPitchIntensityVelocityCurve(?Audio\IControlCurve $oCurve): self {
        $this->oPitchIntensityVelocityCurve = $oCurve;
        return $this;
    }

    /**
     * Set or clear the velocity rate curve for the pitch envelope. Passing null clears the curve.
     *
     * @param  Audio\IControlCurve|null $oCurve
     * @return self
     */
    public function setPitchRateVelocityCurve(?Audio\IControlCurve $oCurve): self {
        $this->oPitchRateVelocityCurve = $oCurve;
        return $this;
    }

    /**
     * Set the velocity. This will be mapped to various parameters by the contol curves.
     *
     * @param  int  $iVelocity
     * @return self
     */
    public function setVelocity(int $iVelocity): self {
        $fCurveInput = (float)$iVelocity;
        if ($oEnvelope = $this->oOscillator->getLevelEnvelope()) {
            $fLevelScale = 1.0;
            $fTimeScale  = 1.0;
            if ($this->oLevelIntensityVelocityCurve) {
                $fLevelScale *= $this->oLevelIntensityVelocityCurve->map($fCurveInput);
            }
            if ($this->oLevelRateVelocityCurve) {
                $fTimeScale *= $this->oLevelRateVelocityCurve->map($fCurveInput);
            }
            $oEnvelope
                ->setLevelScale($fLevelScale)
                ->setTimeScale($fTimeScale)
            ;
        }
        if ($oEnvelope = $this->oOscillator->getPitchEnvelope()) {
            $fLevelScale = 1.0;
            $fTimeScale  = 1.0;
            if ($this->oPitchIntensityVelocityCurve) {
                $fLevelScale *= $this->oPitchIntensityVelocityCurve->map($fCurveInput);
            }
            if ($this->oPitchRateVelocityCurve) {
                $fTimeScale *= $this->oPitchRateVelocityCurve->map($fCurveInput);
            }
            $oEnvelope
                ->setLevelScale($fLevelScale)
                ->setTimeScale($fTimeScale)
            ;
        }
        return $this;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Checks to see if an operator's unique ID is defined anywhere in the modulation tree. If it is, we throw an
     * exception.
     *
     * @param string $sUniqueID
     */
    private function check(string $sUniqueID): void {
        if (isset($this->aModulators[$sUniqueID])) {
            throw new \LogicException('Cyclical operator configuration');
        }
        foreach ($this->aModulators as $oModulator) {
            $oModulator->check($sUniqueID);
        }
    }
}
