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
 * TBNaN
 *
 * Monophonic bassline.
 * Features:
 *    Saw / Square / Pulse waveforms
 *    Resonant Low Pass Filter with adjustable cutoff and resonance
 *    Decay Pulse Level Envelope with selectable rate and target level
 *    Decay Pulse Filter Envelope with selectable rate and target level
 */
class TBNaN implements Audio\IMachine {

    /**
     * Initial defaults
     */
    const
        DEFAULT_AEG_DECAY_RATE = 0.07,
        DEFAULT_FEG_DECAY_RATE = 0.05,
        DEFAULT_CUTOFF         = 1.0,
        DEFAULT_RESONANCE      = 0.7,
        LFO_RATE_MAX           = 32
    ;


    /**
     * Controllers 0x00 - 0x7F are reserved for universal applications.
     * Controllers 0x80 - 0xFF are reserved for machine specific applocations.
     */
    const
        CTRL_WAVE_SELECT      = self::CTRL_CUSTOM + 0,  // Value is enumerated waveform
        CTRL_PWM_WIDTH        = self::CTRL_CUSTOM + 1,  // Value is 0 - 255, ControlCurve mapped
        CTRL_AEG_DECAY_RATE   = self::CTRL_CUSTOM + 2,  // Value is 0 - 255, ControlCurve mapped
        CTRL_AEG_DECAY_LEVEL  = self::CTRL_CUSTOM + 3,  // Value is 0 - 255, ControlCurve mapped
        CTRL_LPF_CUTOFF       = self::CTRL_CUSTOM + 4,  // Value is 0 - 255, ControlCurve mapped
        CTRL_LPF_RESONANCE    = self::CTRL_CUSTOM + 5,  // Value is 0 - 255, ControlCurve mapped
        CTRL_FEG_DECAY_RATE   = self::CTRL_CUSTOM + 6,  // Value is 0 - 255, ControlCurve mapped
        CTRL_FEG_DECAY_LEVEL  = self::CTRL_CUSTOM + 7,  // Value is 0 - 255, ControlCurve mapped
        CTRL_PWM_LFO_DEPTH    = self::CTRL_CUSTOM + 8,  // Value is 0 - 255, ControlCurve mapped
        CTRL_PWM_LFO_RATE     = self::CTRL_CUSTOM + 9,  // Value is 0 - 255, ControlCurve mapped
        CTRL_AMP_LFO_DEPTH    = self::CTRL_CUSTOM + 10, // Value is 0 - 255, ControlCurve mapped
        CTRL_AMP_LFO_RATE     = self::CTRL_CUSTOM + 11, // Value is 0 - 255, ControlCurve mapped
        CTRL_AMP_LPF_DEPTH    = self::CTRL_CUSTOM + 12, // Value is 0 - 255, ControlCurve mapped
        CTRL_AMP_LPF_RATE     = self::CTRL_CUSTOM + 13, // Value is 0 - 255, ControlCurve mapped
        CTRL_LFO_ENABLE       = self::CTRL_CUSTOM + 14, // Value is bitmask of enabled LFOs

        // Bitmask for LFO
        LFO_BIT_PWM = 1,
        LFO_BIT_AMP = 2,
        LFO_BIT_LPF = 4
    ;


//     private const LEVEL_ADJUST = [
//         Audio\Signal\IWaveform::SAW      => 0.33,
//         Audio\Signal\IWaveform::SQUARE   => 0.25,
//         Audio\Signal\IWaveform::PULSE    => 0.25
//     ];

    use TMonophonicMachine, TSimpleVelocity;

    /** @var array<int, Audio\Signal\IWaveform> $aWaveforms */
    private array $aWaveforms = [];

    private Audio\Signal\Oscillator\Sound    $oOscillator;
    private Audio\Signal\Oscillator\LFO      $oPWM;
    private Audio\Signal\IFilter             $oFilter;
    private Audio\Signal\Envelope\DecayPulse $oFEG, $oAEG;

    private ControlAutomator $oControlAutomator;

    /**
     * Constructor
     */
    public function __construct() {
        $this->initWaveforms();
        $this->initOscillator();
        $this->initFilter();
        $this->setVoiceSource($this->oFilter, 1.0);
        $this->oVoice->disable();
        $this->oControlAutomator = new ControlAutomator($this);
    }


    /**
     * @inheritDoc
     */
    public function getControllerDefs(): array {
        return [
            self::CTRL_WAVE_SELECT => (object)[
                'iType'  => self::CTRL_TYPE_SWITCH,
                'iInit'  => Audio\Signal\IWaveform::PULSE,
                'cApply' => function(int $iVoice, int $iValue): void {
                    $this->setWaveform($iValue);
                }

            ],
            self::CTRL_LPF_CUTOFF => (object)[
                'iType'  => self::CTRL_TYPE_KNOB,
                'iInit'  => 255,
                'fMin'   => 0.0,
                'fMax'   => 1.0,
                'cApply' => function(int $iVoice, float $fValue): void {
                    $this->setCutoff($fValue);
                }
            ],
            self::CTRL_LPF_RESONANCE => (object)[
                'iType' => self::CTRL_TYPE_KNOB,
                'iInit' => 179,
                'fMin'  => 0.0,
                'fMax'  => 1.0,
                'cApply' => function(int $iVoice, float $fValue): void {
                    $this->setResonance($fValue);
                }
            ]
        ];
    }


    /**
     * @inheritDoc
     */
    public function setVoiceControllerValue(int $iVoiceNumber, int $iController, int $iValue): self {
        $this->oControlAutomator->setVoiceControllerValue($iVoiceNumber, $iController, $iValue);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function adjustVoiceControllerValue(int $iVoiceNumber, int $iController, int $iDelta) : self {
        $this->oControlAutomator->adjustVoiceControllerValue($iVoiceNumber, $iController, $iDelta);
        return $this;
    }

    /**
     * Set the waveform type
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setWaveform(int $iWaveform): self {
        if (isset($this->aWaveforms[$iWaveform])) {
            $this->oOscillator->setWaveform($this->aWaveforms[$iWaveform]);
        }
        return $this;
    }

    public function setPWMWidth(float $fWidth): self {
        /** @var Audio\Signal\Waveform\Pulse $oWaveform */
        $oWaveform = $this->aWaveforms[Audio\Signal\IWaveform::PULSE];
        $oWaveform->setPulsewidth($fWidth);
        return $this;
    }

    /**
     * Set the amplitude decay
     *
     * @param  float $fHalfLife
     * @return self
     */
    public function setLevelDecay(float $fHalfLife): self {
        $this->oAEG->setHalfLife($fHalfLife);
        return $this;
    }

    /**
     * Set the level decay target
     *
     * @param  float $fTarget
     * @return self
     */
    public function setLevelTarget(float $fTarget): self {
        $this->oAEG->setTarget($fTarget);
        return $this;
    }

    /**
     * Set the cutoff limit for the LPF
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setCutoff(float $fCutoff): self {
        $this->oFilter->setCutoff($fCutoff);
        return $this;
    }

    /**
     * Set the resonance limit for the LPF
     *
     * @param  float $fResonance
     * @return self
     */
    public function setResonance(float $fResonance): self {
        $this->oFilter->setResonance($fResonance);
        return $this;
    }

    /**
     * Set the filter decay rate
     *
     * @param  float $fHalfLife
     * @return self
     */
    public function setCutoffDecay(float $fHalfLife): self {
        $this->oFEG->setHalfLife($fHalfLife);
        return $this;
    }

    /**
     * Set the filter decay target
     *
     * @param  float $fTarget
     * @return self
     */
    public function setCutoffTarget(float $fTarget): self {
        $this->oFEG->setTarget($fTarget);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
        $this->oOscillator->setFrequency(Audio\Note::getFrequency($sNoteName));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber): self {
        $this->oVoice
            ->reset()
            ->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber): self {
        $this->oVoice->disable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    private function initWaveforms(): void {
        $this->oPWM = new Audio\Signal\Oscillator\LFOZeroToOne(
            new Audio\Signal\Waveform\Sine(),
            4.9,
            0.9
        );
        $this->aWaveforms = [
            Audio\Signal\IWaveform::SAW    => new Audio\Signal\Waveform\Saw(),
            Audio\Signal\IWaveform::SQUARE => new Audio\Signal\Waveform\Square(),
            Audio\Signal\IWaveform::PULSE  => new Audio\Signal\Waveform\Pulse(0.25),
        ];
        $this->aWaveforms[Audio\Signal\IWaveform::PULSE]->setPulsewidthModulator($this->oPWM);
    }

    /**
     * Initialise the internal oscillator
     */
    private function initOscillator(): void {
        $this->oAEG = new Audio\Signal\Envelope\DecayPulse(
            1.0,
            self::DEFAULT_AEG_DECAY_RATE
        );
        $this->oOscillator = new Audio\Signal\Oscillator\Sound($this->aWaveforms[Audio\Signal\IWaveform::PULSE]);
        $this->oOscillator->setLevelEnvelope($this->oAEG);
    }

    /**
     * Initialise the internal filter
     */
    private function initFilter(): void {
        $this->oFEG = new Audio\Signal\Envelope\DecayPulse(
            1.0,
            self::DEFAULT_FEG_DECAY_RATE
        );
        $this->oFilter = new Audio\Signal\Filter\LowPass(
            $this->oOscillator,
            self::DEFAULT_CUTOFF,
            self::DEFAULT_RESONANCE,
            $this->oFEG
        );
    }

}


