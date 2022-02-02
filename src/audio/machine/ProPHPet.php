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

/**
 * ProPHPet
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
class ProPHPet implements Audio\IMachine {

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
        Audio\Signal\IWaveform::SINE_SAW_HARD,
        Audio\Signal\IWaveform::SINE_PINCH,
        Audio\Signal\IWaveform::SINE_CUT,
        Audio\Signal\IWaveform::TRIANGLE,
        Audio\Signal\IWaveform::TRIANGLE_HALF_RECT,
        Audio\Signal\IWaveform::SAW,
        Audio\Signal\IWaveform::SQUARE,
        Audio\Signal\IWaveform::POKEY,
        Audio\Signal\IWaveform::NOISE
    ];

    const
        CTRL_OSC_1_RATIO   = self::CTRL_CUSTOM + 0,
        CTRL_OSC_1_DETUNE  = self::CTRL_CUSTOM + 1,
        CTRL_OSC_1_MIX     = self::CTRL_CUSTOM + 2,
        CTRL_PHASE_MOD_IDX = self::CTRL_CUSTOM + 3,
        CTRL_RING_MOD_IDX  = self::CTRL_CUSTOM + 4,
        CTRL_OSC_2_RATIO   = self::CTRL_CUSTOM + 5,
        CTRL_OSC_2_DETUNE  = self::CTRL_CUSTOM + 6,
        CTRL_OSC_2_MIX     = self::CTRL_CUSTOM + 7
    ;

    const CTRL_CUSTOM_NAMES = [
        self::CTRL_OSC_1_RATIO   => 'Oscillator1 Ratio',
        self::CTRL_OSC_1_DETUNE  => 'Oscillator1 Detune',
        self::CTRL_OSC_1_MIX     => 'Oscillator1 Mix',
        self::CTRL_PHASE_MOD_IDX => 'Phase Modulation',
        self::CTRL_RING_MOD_IDX  => 'Ring Modulation',
        self::CTRL_OSC_2_RATIO   => 'Oscillator2 Ratio',
        self::CTRL_OSC_2_DETUNE  => 'Oscillator2 Detune',
        self::CTRL_OSC_2_MIX     => 'Oscillator2 Mix',
    ];

    use TPolyphonicMachine, TSimpleVelocity, TAutomated;

    /**
     * @var array<int, Audio\Signal\IWaveform> $aWaveforms
     */
    private array $aWaveforms = [];

    /**
     * @var array<int, Subtractive\Voice> $aVoice
     */
    private array $aVoice = [];

    /**
     * @var array<int, Audio\Signal\Oscillator\LFO> $aLFO
     */
    private array $aLFO = [];

    private float
        // Modifiers used by the sequence controls
        $fCtrlOscillator1RatioCoarse = 1.0, // 1/16 resolution
        $fCtrlOscillator1RatioFine   = 0.0, // Divides coarse by 256 steps
        $fCtrlOscillator2RatioCoarse = 1.0,
        $fCtrlOscillator2RatioFine   = 0.0
    ;

    private const VOICE_TARGET_MAP = [
        self::TARGET_OSC_1 => Subtractive\Voice::ID_OSC_1,
        self::TARGET_OSC_2 => Subtractive\Voice::ID_OSC_2,
    ];

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
            $this->aVoice[$i] = new Subtractive\Voice();
            $this->setVoiceSource(
                $i,
                $this->aVoice[$i],
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
                    $this->setLFORate($fRateHz, self::TARGET_PITCH_LFO);
                },
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_VIBRATO_DEPTH,
                function (int $iVoice, float $fDepth): void {
                    $this->setLevel($fDepth, self::TARGET_PITCH_LFO);
                },
                0
            ),

            // Tremolo
            new Control\Knob(
                self::CTRL_TREMOLO_RATE,
                function (int $iVoice, float $fRateHz): void {
                    $this->setLFORate($fRateHz, self::TARGET_LEVEL_LFO);
                },
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_TREMOLO_DEPTH,
                function (int $iVoice, float $fDepth): void {
                    $this->setLevel($fDepth, self::TARGET_LEVEL_LFO);
                },
                0
            ),

            // Modulation
            new Control\Knob(
                self::CTRL_PHASE_MOD_IDX,
                function(int $iVoice, float $fValue): void {
                    $this->setPhaseModulationIndex($fValue);
                },
                0
            ),
            new Control\Knob(
                self::CTRL_RING_MOD_IDX,
                function(int $iVoice, float $fValue): void {
                    $this->setRingModulationIndex($fValue);
                },
                0
            ),

            // Oscillator1
            new Control\Switcher(
                self::CTRL_OSC_1_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->assignEnumeratedWaveform($iValue, self::TARGET_OSC_1);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_OSC_1_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator1RatioCoarse = $fValue;
                    $this->setFrequencyRatio($fValue + $this->fCtrlOscillator1RatioFine, self::TARGET_OSC_1);
                },
                16,
                Subtractive\Voice::MIN_RATIO,
                Subtractive\Voice::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_OSC_1_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator1RatioFine = $fValue;
                    $this->setFrequencyRatio($fValue + $this->fCtrlOscillator1RatioCoarse, self::TARGET_OSC_1);
                },
                0,
                0.0,
                Control\Knob::SCALE_UINT8_FIXED_POINT_MAX
            ),

            // Oscillator2
            new Control\Switcher(
                self::CTRL_OSC_2_WAVE,
                function(int $iVoice, int $iValue): void {
                    $this->assignEnumeratedWaveform($iValue, self::TARGET_OSC_2);
                },
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_OSC_2_RATIO,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator2RatioCoarse = $fValue;
                    $this->setFrequencyRatio($fValue + $this->fCtrlOscillator2RatioFine, self::TARGET_OSC_2);
                },
                16,
                Subtractive\Voice::MIN_RATIO,
                Subtractive\Voice::MAX_RATIO
            ),
            new Control\Knob(
                self::CTRL_OSC_1_DETUNE,
                function(int $iVoice, float $fValue): void {
                    $this->fCtrlOscillator2RatioFine = $fValue;
                    $this->setFrequencyRatio($fValue + $this->fCtrlOscillator2RatioCoarse, self::TARGET_OSC_2);
                },
                0,
                0.0,
                Control\Knob::SCALE_UINT8_FIXED_POINT_MAX
            ),

/*
            new Control\Knob(
                self::CTRL_OSC_1_MIX,
                function(int $iVoice, float $fValue): void {
                    $this->setOscillator1Mix($fValue);
                },
                0
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
    public function assignEnumeratedWaveform(int $iWaveform, int $iTarget): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            $oWaveform = $this->aWaveforms[$iWaveform];
            if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
                // Voices
                $iTarget = self::VOICE_TARGET_MAP[$iTarget];
                foreach ($this->aVoice as $oVoice) {
                    $oVoice->setWaveform($iTarget, $oWaveform);
                }
            } else if (isset($this->aLFO[$iTarget])) {
                // LFOs
                $this->aLFO[$iTarget]->setWaveform($oWaveform);
            }
        }
        return $this;
    }

    public function setLevel(float $fLevel, int $iTarget): self {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setMixLevel($iTarget, $fLevel);
            }
        } else if (isset($this->aLFO[$iTarget])) {
            $this->aLFO[$iTarget]->setDepth($fLevel);
        }
        return $this;
    }

    public function setFrequencyRatio(float $fRatio, int $iTarget): self {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setFrequencyRatio($iTarget, $fRatio);
            }
        }
        return $this;
    }

    public function setFrequencyRatioSemitones(float $fSemitones, int $iTarget): self {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            $fRatio  = 2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI);
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setFrequencyRatio($iTarget, $fRatio);
            }
        }
        return $this;
    }

    public function assignLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope, int $iTarget): self {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setLevelEnvelope($iTarget, $oEnvelope);
            }
        } else {
            switch ($iTarget) {
                case self::TARGET_CUTOFF:
                    foreach ($this->aVoice as $oVoice) {
                        $oVoice->setFilterCutoffEnvelope($oEnvelope);
                    }
                    break;
                case self::TARGET_RESONANCE:
                    foreach ($this->aVoice as $oVoice) {
                        $oVoice->setFilterResonanceEnvelope($oEnvelope);
                    }
                    break;
                default:
                    break;
            }
        }
        return $this;
    }

    public function assignPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope, int $iTarget): self {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setPitchEnvelope($iTarget, $oEnvelope);
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
    public function setLFORate(float $fRateHz, int $iTarget): self {
        if (isset($this->aLFO[$iTarget])) {
            $this->aLFO[$iTarget]->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Enable the Pitch LFO. Valid targets are TARGET_OSC_1 and TARGET_OSC_2
     *
     * @param  int $iTarget
     * @return self
     */
    public function enablePitchLFO(int $iTarget): self {
        $this->applyPitchLFO($iTarget, $this->aLFO[self::TARGET_PITCH_LFO]);
        return $this;
    }

    /**
     * Disable the Pitch LFO. Valid targets are TARGET_OSC_1 and TARGET_OSC_2
     *
     * @param  int $iTarget
     * @return self
     */
    public function disablePitchLFO(int $iTarget): self {
        $this->applyPitchLFO($iTarget, null);
        return $this;
    }

    /**
     * Enable the Level LFO. Valid targets are TARGET_OSC_1 and TARGET_OSC_2
     *
     * @param  int $iTarget
     * @return self
     */
    public function enableLevelLFO(int $iTarget): self {
        $this->applyLevelLFO($iTarget, $this->aLFO[self::TARGET_LEVEL_LFO]);
        return $this;
    }

    /**
     * Disable the Level LFO. Valid targets are TARGET_OSC_1 and TARGET_OSC_2
     *
     * @param  int $iTarget
     * @return self
     */
    public function disableLevelLFO(int $iTarget): self {
        $this->applyLevelLFO($iTarget, null);
        return $this;
    }

    public function enableCutoffLFO(): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterCutoffLFO($this->aLFO[self::TARGET_CUTOFF_LFO]);
        }
        return $this;
    }

    public function disableCutoffLFO(): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterCutoffLFO(null);
        }
        return $this;
    }

    public function enableResonanceLFO(): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterResonanceLFO($this->aLFO[self::TARGET_RESONANCE_LFO]);
        }
        return $this;
    }

    public function disableResonanceLFO(): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterResonanceLFO(null);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setPhaseModulationIndex(float $fIndex): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setPhaseModulationIndex($fIndex);
        }
        return $this;
    }

    /**
     * Set the modulation index, i.e. how strongly the modulator output affects the carrier.
     */
    public function setRingModulationIndex(float $fIndex): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setRingModulationIndex($fIndex);
        }
        return $this;
    }

    public function setFilterMode(int $iMode): self {
        if ($iMode >= self::FILTER_OFF && $iMode <= self::FILTER_HIGHPASS) {
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setFilterMode($iMode);
            }
        }
        return $this;
    }

    public function setFilterCutoff(float $fCutoff): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterCutoff($fCutoff);
        }
        return $this;
    }

    public function setFilterResonance(float $fResonance): self {
        foreach ($this->aVoice as $oVoice) {
            $oVoice->setFilterResonance($fResonance);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aVoice[$iVoiceNumber]->setFrequency(Audio\Note::getFrequency($sNoteName));
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

    private function applyPitchLFO(int $iTarget, ?Audio\Signal\Oscillator\LFO $oLFO): void {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setPitchLFO($iTarget, $oLFO);
            }
        }
    }

    private function applyLevelLFO(int $iTarget, ?Audio\Signal\Oscillator\LFO $oLFO): void {
        if (isset(self::VOICE_TARGET_MAP[$iTarget])) {
            $iTarget = self::VOICE_TARGET_MAP[$iTarget];
            foreach ($this->aVoice as $oVoice) {
                $oVoice->setLevelLFO($iTarget, $oLFO);
            }
        }
    }
}
