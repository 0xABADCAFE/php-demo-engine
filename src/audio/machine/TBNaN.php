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
        DEFAULT_RESONANCE      = 0.7
    ;


    /**
     * Controllers 0x00 - 0x7F are reserved for universal applications.
     * Controllers 0x80 - 0xFF are reserved for machine specific applocations.
     */
    const
        CTRL_WAVE_SELECT      = 0x80,
        CTRL_PWM_WIDTH        = 0x81,
        CTRL_AEG_DECAY_RATE   = 0x82,
        CTRL_AEG_DECAY_LEVEL  = 0x83,
        CTRL_LPF_CUTOFF       = 0x84,
        CTRL_LPF_RESONANCE    = 0x85,
        CTRL_FEG_DECAY_RATE   = 0x86,
        CTRL_FEG_DECAY_LEVEL  = 0x87,
        CTRL_MIN_INPUT_VALUE  = 0,
        CTRL_MAX_INPUT_VALUE  = 255
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
    private Audio\ControlCurve\Linear        $oDefaultControlCurve;

    /** @var array<int, Audio\IControlCurve> */
    private array $aControlCurves = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->initWaveforms();
        $this->initOscillator();
        $this->initFilter();
        $this->initControllers();
        $this->setVoiceSource($this->oFilter, 0.125);
        $this->oVoice->disable();
    }

    /**
     * @inheritDoc
     */
    public function setVoiceControllerValue(int $iVoiceNumber, int $iController, int $iValue): self {
        switch ($iController) {
            case self::CTRL_WAVE_SELECT:
                $this->setWaveform($iValue);
                break;

            case self::CTRL_PWM_WIDTH:
                $this->setPWMWidth($this->aControlCurves[$iController]->map((float)$iValue));
                break;

            case self::CTRL_AEG_DECAY_RATE:
                $this->setLevelDecay($this->aControlCurves[$iController]->map((float)$iValue));
                break;

            case self::CTRL_AEG_DECAY_LEVEL:
                $this->setLevelTarget($this->aControlCurves[$iController]->map((float)$iValue));
                break;

            case self::CTRL_LPF_CUTOFF:
                $this->setCutoff($this->aControlCurves[$iController]->map((float)$iValue));
                break;

            case self::CTRL_LPF_RESONANCE:
                $this->setResonance($this->aControlCurves[$iController]->map((float)$iValue));
                break;

            case self::CTRL_FEG_DECAY_RATE:
                $this->setCutoffDecay($this->aControlCurves[$iController]->map((float)$iValue));

            case self::CTRL_FEG_DECAY_LEVEL:
                $this->setCutoffTarget($this->aControlCurves[$iController]->map((float)$iValue));
                break;

        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function adjustVoiceControllerValue(int $iVoiceNumber, int $iController, int $iDelta) : self {
        return $this;
    }

    /**
     * Set the control curve for the enumerated controller. Setting a null control curve will revert to
     * the built-in machine default. If the enumerated controller does not manage a continuous controller
     * value, \OutOfBoundsException is thrown.
     *
     * @param  int $iController
     * @param  Audio\IControlCurve|null $oCurve
     * @return self
     * @throws \OutOfBoundsException
     */
    public function setControllerCurve(int $iController, ?Audio\IControlCurve $oCurve): self {
        if (isset($this->aControlCurves[$iController])) {
            $oControlCurve = $oCurve ?? $this->oDefaultControlCurve;
            $this->aControlCurves[$iController] = $oControlCurve;
        } else {
            throw new \OutOfBoundsException('Invalud controller number #' . $iController);
        }
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

    /**
     * Initialise the curves for the controllers. The default is set to a linear output of 0.0 - 1.0 over the input
     * range 0 - 255.
     */
    private function initControllers(): void {
        $this->oDefaultControlCurve = new Audio\ControlCurve\Linear(
            0.0,
            1.0,
            (float)self::CTRL_MIN_INPUT_VALUE,
            (float)self::CTRL_MAX_INPUT_VALUE
        );

        $this->aControlCurves = [
            self::CTRL_PWM_WIDTH        => $this->oDefaultControlCurve,
            self::CTRL_AEG_DECAY_RATE   => $this->oDefaultControlCurve,
            self::CTRL_AEG_DECAY_LEVEL  => $this->oDefaultControlCurve,
            self::CTRL_LPF_CUTOFF       => $this->oDefaultControlCurve,
            self::CTRL_LPF_RESONANCE    => $this->oDefaultControlCurve,
            self::CTRL_FEG_DECAY_RATE   => $this->oDefaultControlCurve,
            self::CTRL_FEG_DECAY_LEVEL  => $this->oDefaultControlCurve,
        ];
    }
}


