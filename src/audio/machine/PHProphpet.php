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
 * PHProphpet
 *
 * A two oscillator per voice subtractive synth. Each oscillator has a selectable waveform and envelope generators.
 * The oscillators can both be set to arbitrary multiples of the note frequency. VCO1 can modulate both the phase
 * and amplitude of VCO2. VCO1 and VCO2 are then mixed and passed through a VCF stage.
 *
 * VCO1 and VCO2 share common Pitch and Amplitide LFOs.
 *
 *   PEG 1    AEG 1    +-> Phase Mod -+   PEG 2    AEG 2                     CEG  REG (Cutoff / Resonance)
 *     |        |      |              |     |       |                         +-+-+
 *   VCO 1 => VCA 1 ===+--> Amp Mod --+-> VCO 2 => VCA 2 => (Osc 2 mix) ==+     |
 *     |        |      |                    |       |                     +==> VCF ==> Out
 *     |        |      +==================================> (Osc 1 mix) ==+     |
 *     |        |                           |       |                           |
 *     +-------------- Pitch LFO -----------+       |          Cutoff LFO ------+
 *              |                                   |                           |
 *              +-------------- Level LFO-----------+          Resonance LFO ---+
 */
class PHProphpet implements Audio\IMachine {


    /**
     * Enumerated parameter targets
     */
    const
        TARGET_OSC_1         = 0, // Waveform, Pitch Envelope, Level Envelope
        TARGET_OSC_2         = 1, // Waveform, Pitch Envelope, Level Envelope
        TARGET_LEVEL_LFO     = 2, // Waveform, Depth, Rate
        TARGET_PITCH_LFO     = 3, // Waveform, Depth, Rate
        TARGET_CUTOFF_LFO    = 4, // Waveform, Depth, Rate
        TARGET_RESONANCE_LFO = 5, // Waveform, Depth, Rate
        TARGET_CUTOFF        = 6, // Envelope
        TARGET_RESONANCE     = 7  // Envelope
    ;

    const
        FILTER_OFF      = 0,
        FILTER_LOWPASS  = 1,
        FILTER_BANDPASS = 2,
        FILTER_HIGHPASS = 3
    ;

    const WAVETABLE = [
        Audio\Signal\IWaveform::SINE,
        Audio\Signal\IWaveform::SINE_HALF_RECT,
        Audio\Signal\IWaveform::SINE_FULL_RECT,
        Audio\Signal\IWaveform::SINE_SAW,
        Audio\Signal\IWaveform::SINE_PINCH,
        Audio\Signal\IWaveform::SINE_CUT,
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
        CTRL_OSC_1_RATIO   = self::CTRL_CUSTOM + 0,
        CTRL_OSC_1_DETUNE  = self::CTRL_CUSTOM + 1,
        CTRL_OSC_1_MIX     = self::CTRL_CUSTOM + 2,
        CTRL_PHASE_MOD_IDX = self::CTRL_CUSTOM + 3,
        CTRL_AMP_MOD_IDX   = self::CTRL_CUSTOM + 4,
        CTRL_OSC_2_RATIO   = self::CTRL_CUSTOM + 5,
        CTRL_OSC_2_DETUNE  = self::CTRL_CUSTOM + 6,
        CTRL_OSC_2_MIX     = self::CTRL_CUSTOM + 7
    ;

    const CTRL_CUSTOM_NAMES = [
        self::CTRL_OSC_1_RATIO   => 'Oscillator1 Ratio',
        self::CTRL_OSC_1_DETUNE  => 'Oscillator1 Detune',
        self::CTRL_OSC_1_MIX     => 'Oscillator1 Mix',
        self::CTRL_PHASE_MOD_IDX => 'Phase Modulation',
        self::CTRL_AMP_MOD_IDX   => 'Ring Modulation',
        self::CTRL_OSC_2_RATIO   => 'Oscillator2 Ratio',
        self::CTRL_OSC_2_DETUNE  => 'Oscillator2 Detune',
        self::CTRL_OSC_2_MIX     => 'Oscillator2 Mix',
    ];

    use TPolyphonicMachine, TSimpleVelocity, TAutomated;

    /**
     * @var Audio\Signal\IWaveform[] $aWaveforms
     */
    private array $aWaveforms = [];

    /**
     * @var Audio\Signal\Oscillator\Sound[] $aOscillator1
     */
    private array $aOscillator1 = []; // One per voice

    /**
     * @var Audio\Signal\Oscillator\Sound[] $aOscillator2
     */
    private array $aOscillator2 = [];  // One per voice

    /**
     * @var array<int, array<int, Audio\Signal\IFilter>> $aFilter
     */
    private array $aFilter = [];

    /**
     * @var Audio\Signal\AutoMuteSilence<Audio\Signal\FixedMixer>[] $aVoice
     */
    private array $aVoice = [];

    /**
     * @var float[] $aBaseFreq
     */
    private array $aBaseFreq = [];

    /**
     * @var array<int, Audio\Signal\Oscillator\LFO> $aLFO
     */
    private array $aLFO = [];

    /**
     * @var array<int, Audio\Signal\IOscillator> $aWaveAssignable
     */
    private array $aWaveAssignable = [];


    private float
        $fOscillator1Ratio  = 1.0, // Oscillator1 frequency multiplier
        $fOscillator1Mix    = 0.5, // Oscillator1 to output mix level
        $fOscillator2Ratio  = 1.0, // Oscillator2 frequency multiplier
        $fModulationIndex   = 0.5, // Oscillator2 modulation index
        $fOscillator2Mix    = 0.5, // Oscillator2 to output mix level

        // Modifiers used by the sequence controls
        $fCtrlOscillator1RatioCoarse = 1.0, // 1/16 resolution
        $fCtrlOscillator1RatioFine   = 0.0, // Divides coarse by 256 steps
        $fCtrlOscillator2RatioCoarse = 1.0,
        $fCtrlOscillator2RatioFine   = 0.0

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
            $oOscillator1 = new Audio\Signal\Oscillator\Sound(
                $this->aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oOscillator2   = new Audio\Signal\Oscillator\Sound(
                $this->aWaveforms[Audio\Signal\IWaveform::SINE]
            );
            $oOscillator2
                ->setPhaseModulator($oOscillator1)
                ->setPhaseModulationIndex($this->fModulationIndex);
            $oMixer = new Audio\Signal\FixedMixer();
            $oMixer
                ->addInputStream('M', $oOscillator1, $this->fOscillator1Mix)
                ->addInputStream('C', $oOscillator2, $this->fOscillator2Mix)
            ;

            $aFilters = [
                self::FILTER_LOWPASS  => new Audio\Signal\Filter\LowPass($oMixer),
                self::FILTER_BANDPASS => new Audio\Signal\Filter\BandPass($oMixer),
                self::FILTER_HIGHPASS => new Audio\Signal\Filter\HighPass($oMixer)
            ];


            $oMute = new Audio\Signal\AutoMuteSilence($oMixer, 0.05, 1.0/512.0);
            $oMute->disable();

            $this->aOscillator1[$i] = $oOscillator1;
            $this->aOscillator2[$i] = $oOscillator2;

            $this->aFilter[$i]      = $aFilters;

            $this->aVoice[$i]       = $oMute;
            $this->aBaseFreq[$i]    = Audio\Note::CENTRE_FREQUENCY;

            $this->setVoiceSource(
                $i,
                $oMute,
                1.0
            );
        }

        $oPitchLFO     = new Audio\Signal\Oscillator\LFO($this->aWaveforms[Audio\Signal\IWaveform::SINE]);
        $oLevelLFO     = new Audio\Signal\Oscillator\LFOOneToZero($this->aWaveforms[Audio\Signal\IWaveform::SINE]);
        $oCutoffLFO    = new Audio\Signal\Oscillator\LFOOneToZero($this->aWaveforms[Audio\Signal\IWaveform::SINE]);
        $oResonanceLFO = new Audio\Signal\Oscillator\LFOOneToZero($this->aWaveforms[Audio\Signal\IWaveform::SINE]);

        $this->aLFO = [
            self::TARGET_LEVEL_LFO     => $oLevelLFO,
            self::TARGET_PITCH_LFO     => $oPitchLFO,
            self::TARGET_CUTOFF_LFO    => $oCutoffLFO,
            self::TARGET_RESONANCE_LFO => $oResonanceLFO
        ];

        $this->aWaveAssignable = [
            self::TARGET_OSC_1         => $this->aOscillator1,
            self::TARGET_OSC_2         => $this->aOscillator2,
            self::TARGET_LEVEL_LFO     => [$oLevelLFO],
            self::TARGET_PITCH_LFO     => [$oPitchLFO],
            self::TARGET_CUTOFF_LFO    => [$oCutoffLFO],
            self::TARGET_RESONANCE_LFO => [$oResonanceLFO]
        ];

        $this->initAutomated();
    }

    /**
     * @inheritDoc
     */
    public function getControllerDefs(): array {
        return [
/*
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

            // Oscillator1
            new Control\Switcher(
                self::CTRL_OSC_1_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->assignWaveform($iValue, self::TARGET_OSC_1);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_PHASE_MOD_IDX,
                function(int $iVoice, float $fValue): void {
                    $this->setPhaseModulationIndex($fValue);
                },
                0
            ),
            new Control\Knob(
                self::CTRL_OSC_1_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator1RatioCoarse = $fValue;
                    $this->setOscillator1Ratio($fValue + $this->fCtrlOscillator1RatioFine);
                },
                16,
                self::MIN_RATIO,
                self::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_OSC_1_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator1RatioFine = $fValue;
                    $this->setOscillator1Ratio($fValue + $this->fCtrlOscillator1RatioCoarse);
                },
                0,
                0.0,
                (255.0/256.0)
            ),
            new Control\Knob(
                self::CTRL_OSC_1_MIX,
                function(int $iVoice, float $fValue): void {
                    $this->setOscillator1Mix($fValue);
                },
                0
            ),

            // Oscillator2
            new Control\Switcher(
                self::CTRL_OSC_2_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->assignWaveform($iValue, self::TARGET_OSC_2);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_OSC_2_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator2RatioCoarse = $fValue;
                    $this->setOscillator2Ratio($fValue + $this->fCtrlOscillator2RatioFine);
                },
                16,
                self::MIN_RATIO,
                self::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_OSC_2_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator2RatioFine = $fValue;
                    $this->setOscillator2Ratio($fValue + $this->fCtrlOscillator2RatioCoarse);
                },
                0,
                0.0,
                255.0/256.0
            ),
            new Control\Knob(
                self::CTRL_OSC_2_MIX,
                function(int $iVoice, float $fValue): void {
                    $this->setOscillator2Mix($fValue);
                },
                0
            ),
*/
        ];
    }

    /**
     * @inheritDoc
     */
    public function getControllerNames(): array {
        return self::CTRL_NAMES + self::CTRL_CUSTOM_NAMES;
    }

    /**
     * Specify a waveform to use for the given target (if the target supports it)
     *
     * @param  int $iWaveform
     * @param  int $iTarget
     * @return self
     */
    public function assignWaveform(int $iWaveform, int $iTarget): self {
        if (
            isset($this->aWaveforms[$iWaveform]) &&
            isset($this->aWaveAssignable[$iTarget])
        ) {
            $oWaveform = $this->aWaveforms[$iWaveform];
            foreach ($this->aWaveAssignable[$iTarget] as $oOscillator) {
                $oOscillator->setWaveform($oWaveform);
            }
        }
        return $this;
    }

    /**
     * Set the depth of the target LFO.
     *
     * @param  float $fDepth
     * @param  int   $iTarget
     * @return self
     */
    public function setLFODepth(float $fDepth, $iTarget): self {
        if (isset($this->aLFO[$iTarget])) {
            $this->aLFO[$iTarget]->setDepth($fDepth);
        } else {
            echo "Invalid LFO Target ", $iTarget, "n";
        }
        return $this;
    }

    /**
     * Set the rate of the target LFO.
     *
     * @param  float $fRateHz
     * @param  int   $iTarget
     * @return self
     */
    public function setLFORate(float $fRateHz, $iTarget): self {
        if (isset($this->aLFO[$iTarget])) {
            $this->aLFO[$iTarget]->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Enable the Pitch LFO, separately for Oscillator1 and Oscillator2.
     *
     * @param  bool $bOscillator1
     * @param  bool $bOscillator2
     * @return self
     */
    public function enablePitchLFO(bool $bOscillator1, bool $bOscillator2): self {
        $oOscillator1LFO = $bOscillator1 ? $this->aLFO[self::TARGET_PITCH_LFO] : null;
        $oOscillator2LFO = $bOscillator2 ? $this->aLFO[self::TARGET_PITCH_LFO] : null;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aOscillator1[$i]->setPitchModulator($oOscillator1LFO);
            $this->aOscillator2[$i]->setPitchModulator($oOscillator2LFO);
        }
        return $this;
    }

    /**
     * Enable the Level LFO, separately for Oscillator1 and Oscillator2.
     *
     * @param  bool $bOscillator1
     * @param  bool $bOscillator2
     * @return self
     */
    public function enableLevelLFO(bool $bOscillator1, bool $bOscillator2): self {
        $oOscillator1LFO = $bOscillator1 ? $this->aLFO[self::TARGET_LEVEL_LFO] : null;
        $oOscillator2LFO = $bOscillator2 ? $this->aLFO[self::TARGET_LEVEL_LFO] : null;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aOscillator1[$i]->setLevelModulator($oOscillator1LFO);
            $this->aOscillator2[$i]->setLevelModulator($oOscillator2LFO);
        }
        return $this;
    }

    public function assignEnvelope(?Audio\Signal\IEnvelope $oEnvelope, int $iTarget): self {

    }
    /**
     * Set the modulator frequency multiplier as an absolute ratio.
     *
     * @param  float $fRatio
     * @return self
     */
    public function setOscillator1Ratio(float $fRatio): self {
        $this->fOscillator1Ratio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aOscillator1 as $i => $oOscillator1) {
            $oOscillator1->setFrequency($this->aBaseFreq[$i] * $this->fOscillator1Ratio);
        }
        return $this;
    }

    /**
     * Set the modulator frequency multiplier as a relative semitone value.
     */
    public function setOscillator1RatioSemitones(float $fSemitones): self {
        return $this->setOscillator1Ratio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the modulator oscillator
     */
    public function setOscillator1Mix(float $fMix): self {
        $this->fOscillator1Mix = $fMix;
        foreach ($this->aVoice as $i => $oMixer) {
            $oMixer->getStream()->setInputLevel('M', $fMix);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setOscillator1LevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aOscillator1 as $oOscillator1) {
            $oOscillator1->setLevelEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setOscillator1PitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aOscillator1 as $oOscillator1) {
            $oOscillator1->setPitchEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setPhaseModulationIndex(float $fIndex): self {
        $this->fModulationIndex = $fIndex;
        foreach ($this->aOscillator2 as $oOscillator2) {
            $oOscillator2->setPhaseModulationIndex($this->fModulationIndex);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setRingModulationIndex(float $fIndex): self {
        $this->fModulationIndex = $fIndex;
        foreach ($this->aOscillator2 as $oOscillator2) {
            $oOscillator2->setPhaseModulationIndex($this->fModulationIndex);
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as an absolute.
     */
    public function setOscillator2Ratio(float $fRatio): self {
        $this->fOscillator2Ratio = min(max($fRatio, self::MIN_RATIO), self::MAX_RATIO);
        foreach ($this->aOscillator2 as $i => $oOscillator2) {
            $oOscillator2->setFrequency($this->aBaseFreq[$i] * $this->fOscillator2Ratio);
        }
        return $this;
    }

    /**
     * Set the carrier frequency multiplier as a relative semitone value.
     */
    public function setOscillator2RatioSemitones(float $fSemitones): self {
        return $this->setOscillator2Ratio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
    }

    /**
     * Set the output mix level for the carrier oscillator
     */
    public function setOscillator2Mix(float $fMix): self {
        $this->fOscillator2Mix = $fMix;
        return $this;
    }

    /**
     * Set the volume envelope for the carrier oscillator
     */
    public function setOscillator2LevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aOscillator2 as $oOscillator2) {
            $oOscillator2->setLevelEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }

    /**
     * Set the volume envelope for the modulator oscillator
     */
    public function setOscillator2PitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        foreach ($this->aOscillator2 as $oOscillator2) {
            $oOscillator2->setPitchEnvelope($oEnvelope ? clone $oEnvelope : null);
        }
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aBaseFreq[$iVoiceNumber] = $fFrequency = Audio\Note::getFrequency($sNoteName);
            $this->aOscillator2[$iVoiceNumber]->setFrequency($fFrequency * $this->fOscillator2Ratio);
            $this->aOscillator1[$iVoiceNumber]->setFrequency($fFrequency * $this->fOscillator1Ratio);
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
