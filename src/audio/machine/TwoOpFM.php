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

namespace ABadCafe\PDE\Audio\Machine;
use ABadCafe\PDE\Audio;

use function \min, \max;

/**
 * TwoOpFM
 *
 * A simple fixed algorithm 2-operator FM synthesiser:
 *
 *   Modulator -+-> (mod index) -> Carrier -> (carrier mix) -+
 *              |                                            +-> Output
 *              +---------------------------> (mod mix) -----+
 *
 * Modulator and carrier have independent Volume and Pitch envelopes. There are shared pitch and volume LFOs.
 */
class TwoOpFM implements Audio\IMachine {

    const
        MIN_RATIO = 1.0/16.0,
        MAX_RATIO = 16.0
    ;

    use TPolyphonicMachine, TSimpleVelocity, TControllerless;

    /**
     * @var Audio\Signal\IWaveform[] $aWaveforms
     */
    private static array $aWaveforms = [];

    private array
        /**
         * @var Audio\Signal\IOScillator[] $aModulator
         */
        $aModulator    = [], // One per voice

        /**
         * @var Audio\Signal\IOScillator[] $aCarrier
         */
        $aCarrier      = [],  // One per voice

        /**
         * @var Audio\Signal\FixedMixer[] $aVoice
         */
        $aVoice        = [],

        /**
         * @var float[] $aBaseFreq
         */
        $aBaseFreq     = []
    ;

    private ?Audio\Signal\IEnvelope
        $oModulatorLevelEnv  = null,
        $oModulatorPitchEnv  = null,
        $oCarrierLevelEnv    = null,
        $oCarrierPitchEnv    = null
    ;

    private Audio\Signal\Oscillator\LFO
        $oPitchLFO,
        $oLevelLFO
    ;

    private float
        $fModulatorRatio  = 1.001, // Modulator frequency multiplier
        $fModulatorMix    = 0.0,  // Modulator to output mix level
        $fCarrierRatio    = 1.0, // Carrier frequency multiplier
        $fModulationIndex = 0.5, // Carrier modulation index
        $fCarrierMix      = 1.0  // Carrier to output mix level
    ;

    /**
     * Constructor
     *
     * @param int $iNumVoices
     */
    public function __construct(int $iNumVoices) {
        self::initShared();
        $this->initPolyphony($iNumVoices);

        $fMixLevel = 0.5 / $this->iNumVoices;

        for ($i = 0; $i < $this->iNumVoices; ++$i) {

            // Create the fixed topology.
            $oModulator = new Audio\Signal\Oscillator\Sound(
                self::$aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oCarrier   = new Audio\Signal\Oscillator\Sound(
                self::$aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oCarrier
                ->setPhaseModulator($oModulator)
                ->setPhaseModulationIndex($this->fModulationIndex);
            $oMixer = new Audio\Signal\FixedMixer();
            $oMixer
                ->addInputStream('M', $oModulator, $this->fModulatorMix)
                ->addInputStream('C', $oCarrier, $this->fCarrierMix)
                ->disable()
            ;
            $this->aModulator[$i] = $oModulator;
            $this->aCarrier[$i]   = $oCarrier;
            $this->aVoice[$i]     = $oMixer;
            $this->aBaseFreq[$i]  = Audio\Note::CENTRE_FREQUENCY;
            $this->setVoiceSource($i, $oMixer, $fMixLevel);
        }

        $this->oPitchLFO = new Audio\Signal\Oscillator\LFO(self::$aWaveforms[Audio\Signal\IWaveform::SINE]);
        $this->oLevelLFO = new Audio\Signal\Oscillator\LFOOneToZero(self::$aWaveforms[Audio\Signal\IWaveform::SINE]);
    }

    /**
     * Set the (enumerated) Pitch LFO waveform shape
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setPitchLFOWaveform(int $iWaveform) : self {
        if (isset(self::$aWaveforms[$iWaveform])) {
            $this->oPitchLFO->setWaveform(self::$aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * Set the depth of the Pitch LFO, in semitones.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setPitchLFODepth(float $fDepth) : self {
        $this->oPitchLFO->setDepth($fDepth);
        return $this;
    }

    /**
     * Set the rate of the Pitch LFO, in Hz.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setPitchLFORate(float $fRate) : self {
        $this->oPitchLFO->setFrequency($fRate);
        return $this;
    }

    /**
     * Set the (enumerated) Level LFO waveform shape
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setLevelLFOWaveform(int $iWaveform) {
        if (isset(self::$aWaveforms[$iWaveform])) {
            $this->oLevelLFO->setWaveform(self::$aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * Set the depth of the Level LFO, i.e. how strongly the LFO attenuates. A value of 1.0 attenuates to silence.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setLevelLFODepth(float $fDepth) : self {
        $this->oLevelLFO->setDepth($fDepth);
        return $this;
    }

    /**
     * Set the rate of the Level LFO, in Hz.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setLevelLFORate(float $fRate) : self {
        $this->oLevelLFO->setFrequency($fRate);
        return $this;
    }

    /**
     * Enable the Pitch LFO, separately for Modulator and Carrier.
     *
     * @param  bool $bModulator
     * @param  bool $bCarrier
     * @return self
     */
    public function enablePitchLFO(bool $bModulator, bool $bCarrier) : self {
        $oModulatorLFO = $bModulator ? $this->oPitchLFO : null;
        $oCarrierLFO   = $bCarrier   ? $this->oPitchLFO : null;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aModulator[$i]->setPitchModulator($oModulatorLFO);
            $this->aCarrier[$i]->setPitchModulator($oCarrierLFO);
        }
        return $this;
    }

    /**
     * Enable the Level LFO, separately for Modulator and Carrier.
     *
     * @param  bool $bModulator
     * @param  bool $bCarrier
     * @return self
     */
    public function enableLevelLFO(bool $bModulator, bool $bCarrier) : self {
        $oModulatorLFO = $bModulator ? $this->oLevelLFO : null;
        $oCarrierLFO   = $bCarrier   ? $this->oLevelLFO : null;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aModulator[$i]->setLevelModulator($oModulatorLFO);
            $this->aCarrier[$i]->setLevelModulator($oCarrierLFO);
        }
        return $this;
    }

    /**
     * Set the enumerated waveform type for the modulator oscillator. Additionally one of the standard waveform
     * rectifiers can be applied.
     *
     * @param  int $iWaveform
     * @param  int $iModifier
     * @return self
     */
    public function setModulatorWaveform(int $iWaveform, int $iModifier = Audio\Signal\Waveform\Rectifier::NONE) : self {
        if (isset(self::$aWaveforms[$iWaveform])) {
            foreach ($this->aModulator as $oModulator) {
                $oModulator->setWaveform(
                    Audio\Signal\Waveform\Rectifier::createStandard(
                        self::$aWaveforms[$iWaveform],
                        $iModifier
                    )
                );
            }
        }
        return $this;
    }

    /**
     * Set the modulator frequency multiplier as an absolute ratio.
     *
     * @param  float $fRatio
     * @return self
     */
    public function setModulatorRatio(float $fRatio) : self {
        $this->fModulatorRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aModulator as $i => $oModulator) {
            $oModulator->setFrequency($this->aBaseFreq[$i] * $this->fModulatorRatio);
        }
        return $this;
    }

    /**
     * Set the modulator frequency multiplier as a relative semitone value.
     */
    public function setModulatorRatioSemitones(float $fSemitones) : self {
        return $this->setModulatorRatio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the modulator oscillator
     */
    public function setModulatorMix(float $fMix) : self {
        $this->fModulatorMix = $fMix;
        foreach ($this->aVoice as $i => $oMixer) {
            $oMixer->setInputLevel('M', $fMix);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setModulatorLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope) : self {
        $this->oModulatorLevelEnv = $oEnvelope;
        foreach ($this->aModulator as $oModulator) {
            $oModulator->setLevelEnvelope(clone $oEnvelope);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setModulatorPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope) : self {
        $this->oModulatorPitchEnv = $oEnvelope;
        foreach ($this->aModulator as $oModulator) {
            $oModulator->setPitchEnvelope(clone $oEnvelope);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setModulationIndex(float $fIndex) : self {
        $this->fModulationIndex = $fIndex;
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setPhaseModulationIndex($this->fModulationIndex);
        }
        return $this;
    }

    /**
     * Set the enumerated waveform type for the modulator oscillator. Additionally one of the standard waveform
     * rectifiers can be applied.
     *
     * @param  int $iWaveform
     * @param  int $iModifier
     * @return self
     */
    public function setCarrierWaveform(int $iWaveform, int $iModifier = Audio\Signal\Waveform\Rectifier::NONE) : self {
        if (isset(self::$aWaveforms[$iWaveform])) {
            foreach ($this->aCarrier as $oCarrier) {
                $oCarrier->setWaveform(
                    Audio\Signal\Waveform\Rectifier::createStandard(
                        self::$aWaveforms[$iWaveform],
                        $iModifier
                    )
                );
            }
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as an absolute.
     */
    public function setCarrierRatio(float $fRatio) : self {
        $this->fCarrierRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aCarrier as $i => $oCarrier) {
            $oCarrier->setFrequency($this->aBaseFreq[$i] * $this->fCarrierRatio);
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as a relative semitone value.
     */
    public function setCarrierRatioSemitones(float $fSemitones) : self {
        return $this->setCarrierRatio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the carrier oscillator
     */
    public function setCarrierMix(float $fMix) : self {
        $this->fCarrierMix = $fMix;
        return $this;
    }

    /**
     * Set the volume envelope for the carrier oscillator
     */
    public function setCarrierLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope) : self {
        $this->oCarrierLevelEnv = $oEnvelope;
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setLevelEnvelope(clone $oEnvelope);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setCarrierPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope) : self {
        $this->oCarrierPitchEnv = $oEnvelope;
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setPitchEnvelope(clone $oEnvelope);
        }
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName) : self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aBaseFreq[$iVoiceNumber] = $fFrequency = Audio\Note::getFrequency($sNoteName);
            $this->aCarrier[$iVoiceNumber]->setFrequency($fFrequency * $this->fCarrierRatio);
            $this->aModulator[$iVoiceNumber]->setFrequency($fFrequency * $this->fModulatorRatio);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber) : self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aVoice[$iVoiceNumber]
                ->reset()
                ->enable();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber) : self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aVoice[$iVoiceNumber]->disable();
        }
        return $this;
    }


    private static function initShared() {
        if (empty(self::$aWaveforms)) {
            self::$aWaveforms = [
                Audio\Signal\IWaveform::SINE     => new Audio\Signal\Waveform\Sine(),
                Audio\Signal\IWaveform::TRIANGLE => new Audio\Signal\Waveform\Triangle(),
                Audio\Signal\IWaveform::SAW      => new Audio\Signal\Waveform\Saw(),
                Audio\Signal\IWaveform::SQUARE   => new Audio\Signal\Waveform\Square()
            ];
        }
    }
}
