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

namespace ABadCafe\PDE\Audio\Signal\Oscillator;

use ABadCafe\PDE\Audio;

/**
 * Basic sound oscillator
 */
class Sound extends Base {

    const
        INV_TWELVETH  = 1.0 / 12.0, // For conversion of semitone range pitch modulator to absolute multipliers
        MIN_FREQUENCY = 6.875,      // Low
        DEF_FREQUENCY = 440.0,      // A4
        MAX_FREQUENCY = 14080.0     // A9
    ;

    protected ?Audio\Signal\IStream
        $oPitchModulator = null,
        $oPhaseModulator = null,
        $oLevelModulator = null
    ;

    protected ?Audio\Signal\IEnvelope $oEnvelope = null;

    protected float
        $fPhaseModulationIndex = 1.0,
        $fLevelModulationIndex = 1.0
    ;

    /**
     * @inheritDoc
     */
    public function reset() : self {
        parent::reset();
        $this->oPitchModulator && $this->oPitchModulator->reset();
        $this->oPhaseModulator && $this->oPhaseModulator->reset();
        $this->oLevelModulator && $this->oLevelModulator->reset();
        $this->oEnvelope       && $this->oEnvelope->reset();
    }

    /**
     * Set a pitch modulator stream to use. The values from the stream are interpreted as fractional semitones
     * such that an instantaneous value of 0.0 means no shift, 1.0 means up 1 semitone, -12.0 means down one octave.
     *
     * Passing null removes all pitch modulation.
     *
     * @param Audio\Signal\IStream $oModulator
     */
    public function setPitchModulator(?Audio\Signal\IStream $oModulator) : self {
        $this->oPitchModulator = $oModulator;
        return $this;
    }

    /**
     * Set a phase modulator stream to use. The values from the stream are interpreted as full duty cycles such that
     * an instantaneous value of 0.0 means no phase adjustment, 1.0 means one full duty cycle ahead, -0.5 means half
     * a duty cycle behind. The intended use case is for FM synthesis.
     *
     * Passing null removes all phase modulation.
     *
     * @param  Audio\Signal\IStream $oModulator
     * @return self
     */
    public function setPhaseModulator(?Audio\Signal\IStream $oModulator) : self {
        $this->oPhaseModulator = $oModulator;
        return $this;
    }

    /**
     *  Sets the overal Phase Modulation strength.
     *
     * @param  float $fModulationIndex
     * @return self
     */
    public function setPhaseModulationIndex(float $fModulationIndex) : self {
        $this->fPhaseModulationIndex = $fModulationIndex;
        return $this;
    }

    /**
     * Set an amplitude modulator stream to use. This is intended for tremelo type effects and there is a separate
     * facility for setting envelopes.
     *
     * Passing null removes all phase modulation.
     *
     * @param  Audio\Signal\IStream $oModulator
     * @return self
     */
    public function setLevelModulator(?Audio\Signal\IStream $oModulator) : self {
        $this->oLevelModulator = $oModulator;
        return $this;
    }

    /**
     *  Sets the overal Phase Modulation strength.
     *
     * @param  float $fModulationIndex
     * @return self
     */
    public function setLevelModulationIndex(float $fModulationIndex) : self {
        $this->fLevelModulationIndex = $fModulationIndex;
        return $this;
    }

    /**
     * Set the specific output envelope to use.
     *
     * @param  ?Audio\Signal\IEnvelope $oEnvelope
     * @return self
     */
    public function setEnvelope(?Audio\Signal\IEnvelope $oEnvelope) : self {
        $this->oEnvelope = $oEnvelope;
        return $this;
    }

    /**
     * Calculates a new audio packet
     *
     * @return Signal\Audio\Packet;
     */
    protected function emitNew() : Audio\Signal\Packet {

        if ($this->oPitchModulator) {
            $oPitchShifts = $this->oPitchModulator->emit($this->iLastIndex);

            // Every sample point has a new frequency, but we can't just use the instantaneous Waveform value for
            // that as it would be the value that the function has if it was always at that frequency.
            // Therefore we must also correct the phase for every sample point too. The phase correction is
            // accumulated, which is equivalent to integrating over the time step.
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                $fNextFrequencyMultiplier = 2.0 ** ($oPitchShifts[$i] * self::INV_TWELVETH);
                $fNextFrequency           = $this->fFrequency * $fNextFrequencyMultiplier;
                $fTime                    = $this->fTimeStep  * $this->iSamplePosition++;
                $this->oWaveformInput[$i] = ($this->fCurrentFrequency * $fTime) + $this->fPhaseCorrection;
                $this->fPhaseCorrection   += $fTime * ($this->fCurrentFrequency - $fNextFrequency);
                $this->fCurrentFrequency  = $fNextFrequency;
            }
        } else {
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                $this->oWaveformInput[$i] = $this->fScaleVal * $this->iSamplePosition++;
            }
        }

        if ($this->oPhaseModulator) {
            // We have somthing modulating our basic phase. Thankfully this is just additive. We assume the
            // phase modulation is normalised, such that 1.0 is a complete full cycle of our waveform.
            // We simply multiply the shift by our Waveform's period value to get this.
            $oPhaseShifts = $this->oPhaseModulator->emit($this->iLastIndex);
            $fPeriod = $this->fPhaseModulationIndex * $this->fWaveformPeriod;
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                $this->oWaveformInput[$i] += $fPeriod * $oPhaseShifts[$i];
            }
        }

        $this->oLastOutput = $this->oWaveform->map($this->oWaveformInput);

        if ($this->oLevelModulator) {
            $oLevel = clone $this->oLevelModulator->emit($this->iLastIndex);
            $oLevel->scaleBy($this->fLevelModulationIndex);
            $this->oLastOutput->modulateWith($oLevel);
        }

        if ($this->oEnvelope) {
            $this->oLastOutput->modulateWith($this->oEnvelope->emit($this->iLastIndex));
        }

        return $this->oLastOutput;
    }
}

