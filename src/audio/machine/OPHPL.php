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
use function \max, \min;

/**
 * OPHPL
 *
 * A simple fixed algorithm 2-operator FM synthesiser:
 *
 *   Modulator -+-> (mod index) -> Carrier -> (carrier mix) -+
 *              |                                            +-> Output
 *              +---------------------------> (mod mix) -----+
 *
 * Modulator and carrier have independent Volume and Pitch envelopes. There are shared pitch and volume LFOs.
 */
class OPHPL implements Audio\IMachine {

    const WAVETABLE = [
        Audio\Signal\IWaveform::SINE,
        Audio\Signal\IWaveform::SINE_HALF_RECT,
        Audio\Signal\IWaveform::SINE_FULL_RECT,
        Audio\Signal\IWaveform::SINE_SAW,
        Audio\Signal\IWaveform::SINE_PINCH,
        Audio\Signal\IWaveform::SINE_CUT,
        Audio\Signal\IWaveform::SINE_SAW_HARD,
        Audio\Signal\IWaveform::TRIANGLE,
        Audio\Signal\IWaveform::TRIANGLE_HALF_RECT,
        Audio\Signal\IWaveform::SAW,
        Audio\Signal\IWaveform::SQUARE,
    ];

    const
        MIN_RATIO = 1.0/16.0,
        MAX_RATIO = 16.0
    ;

    const
        CTRL_MODULATOR_RATIO  = self::CTRL_CUSTOM + 0,
        CTRL_MODULATOR_DETUNE = self::CTRL_CUSTOM + 1,
        CTRL_MODULATOR_MIX    = self::CTRL_CUSTOM + 2,
        CTRL_MODULATION_INDEX = self::CTRL_CUSTOM + 3,
        CTRL_CARRIER_RATIO    = self::CTRL_CUSTOM + 4,
        CTRL_CARRIER_DETUNE   = self::CTRL_CUSTOM + 5,
        CTRL_CARRIER_MIX      = self::CTRL_CUSTOM + 6
    ;

    const CTRL_CUSTOM_NAMES = [
        self::CTRL_MODULATOR_RATIO  => 'Modulator Ratio',
        self::CTRL_MODULATOR_DETUNE => 'Modulator Detune',
        self::CTRL_MODULATOR_MIX    => 'Modulator Mix',
        self::CTRL_MODULATION_INDEX => 'Modulation Index',
        self::CTRL_CARRIER_RATIO    => 'Carrier Ratio',
        self::CTRL_CARRIER_DETUNE   => 'Carrier Detune',
        self::CTRL_CARRIER_MIX      => 'Carrier Mix',
    ];

    use TPolyphonicMachine, TSimpleVelocity, TAutomated;

    /**
     * @var Audio\Signal\IWaveform[] $aWaveforms
     */
    private array $aWaveforms = [];

    /**
     * @var Audio\Signal\Oscillator\Sound[] $aModulator
     */
    private array $aModulator    = []; // One per voice

    /**
     * @var Audio\Signal\Oscillator\Sound[] $aCarrier
     */
    private array $aCarrier      = [];  // One per voice

    /**
     * @var Audio\Signal\Operator\AutoMuteSilence<Audio\Signal\Operator\FixedMixer>[] $aVoice
     */
    private array $aVoice        = [];

    /**
     * @var float[] $aBaseFreq
     */
    private array $aBaseFreq     = [];

    private Audio\Signal\Oscillator\LFO
        $oPitchLFO,
        $oLevelLFO
    ;


    private float
        $fModulatorRatio  = 1.0, // Modulator frequency multiplier
        $fModulatorMix    = 0.0, // Modulator to output mix level
        $fCarrierRatio    = 1.0, // Carrier frequency multiplier
        $fModulationIndex = 0.5, // Carrier modulation index
        $fCarrierMix      = 1.0,  // Carrier to output mix level

        // Modifiers used by the sequence controls
        $fCtrlModulatorRatioCoarse = 1.0, // 1/16 resolution
        $fCtrlModulatorRatioFine   = 0.0, // Divides coarse by 256 steps
        $fCtrlCarrierRatioCoarse   = 1.0,
        $fCtrlCarrierRatioFine     = 0.0

    ;

    /**
     * Constructor
     *
     * @param int $iNumVoices
     */
    public function __construct(int $iNumVoices) {
        $this->aWaveforms = Audio\Signal\Waveform\Flyweight::get()
            ->getWaveforms(self::WAVETABLE);
        $this->initPolyphony($iNumVoices);

        for ($i = 0; $i < $this->iNumVoices; ++$i) {

            // Create the fixed topology.
            $oModulator = new Audio\Signal\Oscillator\Sound(
                $this->aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oCarrier   = new Audio\Signal\Oscillator\Sound(
                $this->aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oCarrier
                ->setPhaseModulator($oModulator)
                ->setPhaseModulationIndex($this->fModulationIndex);
            $oMixer = new Audio\Signal\Operator\FixedMixer();
            $oMixer
                ->addInputStream('M', $oModulator, $this->fModulatorMix)
                ->addInputStream('C', $oCarrier, $this->fCarrierMix)
            ;

            $oMute = new Audio\Signal\Operator\AutoMuteSilence($oMixer, 0.05, 1.0/512.0);
            $oMute->disable();

            $this->aModulator[$i] = $oModulator;
            $this->aCarrier[$i]   = $oCarrier;
            $this->aVoice[$i]     = $oMute;
            $this->aBaseFreq[$i]  = Audio\Note::CENTRE_FREQUENCY;

            $this->setVoiceSource(
                $i,
                $oMute,
                1.0
            );
        }

        $this->oPitchLFO = new Audio\Signal\Oscillator\LFO($this->aWaveforms[Audio\Signal\IWaveform::SINE]);
        $this->oLevelLFO = new Audio\Signal\Oscillator\LFOOneToZero($this->aWaveforms[Audio\Signal\IWaveform::SINE]);
        $this->initAutomated();
    }

    /**
     * @inheritDoc
     */
    public function getControllerDefs(): array {
        return [
            // Vibrato
            new Control\Knob(
                self::CTRL_VIBRATO_RATE,
                function (int $iVoice, float $fRateHz): void {
                    $this->setPitchLFORate($fRateHz);
                },
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_VIBRATO_DEPTH,
                function (int $iVoice, float $fDepth): void {
                    $this->setPitchLFODepth($fDepth);
                },
                0
            ),
            // Tremolo
            new Control\Knob(
                self::CTRL_TREMOLO_RATE,
                function (int $iVoice, float $fRateHz): void {
                    $this->setLevelLFORate($fRateHz);
                },
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_TREMOLO_DEPTH,
                function (int $iVoice, float $fDepth): void {
                    $this->setLevelLFODepth($fDepth);
                },
                0
            ),

            // Modulator
            new Control\Switcher(
                self::CTRL_OSC_2_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->setModulatorWaveform($iValue);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_MODULATION_INDEX,
                function(int $iVoice, float $fValue): void {
                    $this->setModulationIndex($fValue);
                },
                0
            ),
            new Control\Knob(
                self::CTRL_MODULATOR_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlModulatorRatioCoarse = $fValue;
                    $this->setModulatorRatio($fValue + $this->fCtrlModulatorRatioFine);
                },
                16,
                self::MIN_RATIO,
                self::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_MODULATOR_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlModulatorRatioFine = $fValue;
                    $this->setModulatorRatio($fValue + $this->fCtrlModulatorRatioCoarse);
                },
                0,
                0.0,
                (255.0/256.0)
            ),
            new Control\Knob(
                self::CTRL_MODULATOR_MIX,
                function(int $iVoice, float $fValue): void {
                    $this->setModulatorMix($fValue);
                },
                0
            ),

            // Carrier
            new Control\Switcher(
                self::CTRL_OSC_1_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->setCarrierWaveform($iValue);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_CARRIER_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlCarrierRatioCoarse = $fValue;
                    $this->setCarrierRatio($fValue + $this->fCtrlCarrierRatioFine);
                },
                16,
                self::MIN_RATIO,
                self::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_CARRIER_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlCarrierRatioFine = $fValue;
                    $this->setCarrierRatio($fValue + $this->fCtrlCarrierRatioCoarse);
                },
                0,
                0.0,
                255.0/256.0
            ),
            new Control\Knob(
                self::CTRL_CARRIER_MIX,
                function(int $iVoice, float $fValue): void {
                    $this->setCarrierMix($fValue);
                },
                0
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getControllerNames(): array {
        return self::CTRL_NAMES + self::CTRL_CUSTOM_NAMES;
    }

    /**
     * Set the (enumerated) Pitch LFO waveform shape
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setPitchLFOWaveform(int $iWaveform): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            $this->oPitchLFO->setWaveform($this->aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * Set the depth of the Pitch LFO, in semitones.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setPitchLFODepth(float $fDepth): self {
        $this->oPitchLFO->setDepth($fDepth);
        return $this;
    }

    /**
     * Set the rate of the Pitch LFO, in Hz.
     *
     * @param  float $fRate
     * @return self
     */
    public function setPitchLFORate(float $fRate): self {
        $this->oPitchLFO->setFrequency($fRate);
        return $this;
    }

    /**
     * Set the (enumerated) Level LFO waveform shape
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setLevelLFOWaveform(int $iWaveform): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            $this->oLevelLFO->setWaveform($this->aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * Set the depth of the Level LFO, i.e. how strongly the LFO attenuates. A value of 1.0 attenuates to silence.
     *
     * @param  float $fDepth
     * @return self
     */
    public function setLevelLFODepth(float $fDepth): self {
        $this->oLevelLFO->setDepth($fDepth);
        return $this;
    }

    /**
     * Set the rate of the Level LFO, in Hz.
     *
     * @param  float $fRate
     * @return self
     */
    public function setLevelLFORate(float $fRate): self {
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
    public function enablePitchLFO(bool $bModulator, bool $bCarrier): self {
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
    public function enableLevelLFO(bool $bModulator, bool $bCarrier): self {
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
     * @return self
     */
    public function setModulatorWaveform(int $iWaveform): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            foreach ($this->aModulator as $oModulator) {
                $oModulator->setWaveform($this->aWaveforms[$iWaveform]);
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
    public function setModulatorRatio(float $fRatio): self {
        $this->fModulatorRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aModulator as $i => $oModulator) {
            $oModulator->setFrequency($this->aBaseFreq[$i] * $this->fModulatorRatio);
        }
        return $this;
    }

    /**
     * Set the level of self modulation for the modulator.
     *
     * @param  float $fFeedback
     * @return self
     */
    public function setModulatorFeedbackIndex(float $fFeedback): self {
        foreach ($this->aModulator as $oModulator) {
            $oModulator->setPhaseFeedbackIndex($fFeedback);
        }
        return $this;
    }

    /**
     * Set the modulator frequency multiplier as a relative semitone value.
     */
    public function setModulatorRatioSemitones(float $fSemitones): self {
        return $this->setModulatorRatio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the modulator oscillator
     */
    public function setModulatorMix(float $fMix): self {
        $this->fModulatorMix = $fMix;
        foreach ($this->aVoice as $i => $oMixer) {
            $oMixer->getStream()->setInputLevel('M', $fMix);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setModulatorLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aModulator as $oModulator) {
            $oModulator->setLevelEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setModulatorPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aModulator as $oModulator) {
            $oModulator->setPitchEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setModulationIndex(float $fIndex): self {
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
     * @return self
     */
    public function setCarrierWaveform(int $iWaveform): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            foreach ($this->aCarrier as $oCarrier) {
                $oCarrier->setWaveform($this->aWaveforms[$iWaveform]);
            }
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as an absolute.
     */
    public function setCarrierRatio(float $fRatio): self {
        $this->fCarrierRatio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aCarrier as $i => $oCarrier) {
            $oCarrier->setFrequency($this->aBaseFreq[$i] * $this->fCarrierRatio);
        }
        return $this;
    }

    /**
     * Set the level of self modulation for the carrier. Eat that, Chowning.
     *
     * @param  float $fFeedback
     * @return self
     */
    public function setCarrierFeedbackIndex(float $fFeedback): self {
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setPhaseFeedbackIndex($fFeedback);
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as a relative semitone value.
     */
    public function setCarrierRatioSemitones(float $fSemitones): self {
        return $this->setCarrierRatio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the carrier oscillator
     */
    public function setCarrierMix(float $fMix): self {
        $this->fCarrierMix = $fMix;
        foreach ($this->aVoice as $i => $oMixer) {
            $oMixer->getStream()->setInputLevel('C', $fMix);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the carrier oscillator
     */
    public function setCarrierLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setLevelEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setCarrierPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aCarrier as $oCarrier) {
            $oCarrier->setPitchEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
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
    public function startVoice(int $iVoiceNumber): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aVoice[$iVoiceNumber]
                ->reset()
                ->enable();
            $this->handleVoiceStarted();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aVoice[$iVoiceNumber]->disable();
        }
        return $this;
    }
}
