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
        INV_TWELVETH   = 1.0 / 12.0,  // For conversion of semitone range pitch modulator to absolute multipliers
        MIN_FREQUENCY  = 6.875,       // Low
        DEF_FREQUENCY  = 440.0,       // A4
        MAX_FREQUENCY  = 14080.0,     // A9,
        ANTIALIAS_OFF  = 0,
        ANTIALIAS_ON   = 1,
        ANTIALIAS_AUTO = 2
    ;

    protected ?Audio\Signal\IStream
        $oPitchModulator = null,
        $oPhaseModulator = null,
        $oLevelModulator = null
    ;

    protected ?Audio\Signal\IEnvelope
        $oLevelEnvelope  = null,
        $oPitchEnvelope  = null
    ;

    protected float
        $fPhaseModulationIndex = 1.0,
        $fLevelModulationIndex = 1.0,

        // Self modulation
        $fPhaseFeedbackIndex   = 0.0,
        $fFeedBack1              = 0.0,
        $fFeedBack2              = 0.0,

        // Antialias smoothing
        $fAAPrev1              = 0.0,
        $fAAPrev2              = 0.0,
        $fAAPrev3              = 0.0,
        $fAAPrev4              = 0.0
    ;

    private int  $iAntialiasMode = self::ANTIALIAS_AUTO;
    private bool $bAntialias     = false;

    /**
     * @var array<int, callable> $aInputStages
     */
    protected array $aInputStages = [];

    /**
     * @var array<int, callable> $aOutputStages
     */
    protected array $aOutputStages = [];

    /** @var callable $cInputStage */
    protected $cInputStage;

    /** @var callable $cOutputStage */
    protected $cOutputStage;

    /**
     * @inheritDoc
     */
    public function __construct(
        ?Audio\Signal\IWaveform $oWaveform = null,
        float $fFrequency = 0.0,
        float $fPhase     = 0.0
    ) {
        parent::__construct($oWaveform, $fFrequency, $fPhase);
        $this->createInputStages();
        $this->createOutputStages();
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        parent::reset();
        if ($this->oPitchModulator) {
            $this->oPitchModulator->reset();
        }
        if ($this->oPhaseModulator) {
            $this->oPhaseModulator->reset();
        }
        if ($this->oLevelModulator) {
            $this->oLevelModulator->reset();
        }
        if ($this->oLevelEnvelope) {
            $this->oLevelEnvelope->reset();
        }
        if ($this->oPitchEnvelope) {
            $this->oPitchEnvelope->reset();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAntialiasMode(int $iMode): self {
        if ($iMode >= self::ANTIALIAS_OFF && $iMode <= self::ANTIALIAS_AUTO) {
            $this->iAntialiasMode = $iMode;
            $this->configureAntialias();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWaveform(?Audio\Signal\IWaveform $oWaveform): self {
        parent::setWaveform($oWaveform);
        $this->configureAntialias();
        return $this;
    }


    /**
     * Set a pitch modulator stream to use. The values from the stream are interpreted as fractional semitones
     * such that an instantaneous value of 0.0 means no shift, 1.0 means up 1 semitone, -12.0 means down one octave.
     *
     * Passing null removes all pitch modulation.
     *
     * @param Audio\Signal\IStream|null $oModulator
     */
    public function setPitchModulator(?Audio\Signal\IStream $oModulator): self {
        $this->oPitchModulator = $oModulator;
        $this->configureInputStage();
        return $this;
    }

    /**
     * @return Audio\Signal\IStream|null
     */
    public function getPitchModulator(): ?Audio\Signal\IStream {
        return $this->oPitchModulator;
    }

    /**
     * Set a phase modulator stream to use. The values from the stream are interpreted as full duty cycles such that
     * an instantaneous value of 0.0 means no phase adjustment, 1.0 means one full duty cycle ahead, -0.5 means half
     * a duty cycle behind. The intended use case is for FM synthesis.
     *
     * Passing null removes all phase modulation.
     *
     * @param  Audio\Signal\IStream|null $oModulator
     * @return self
     */
    public function setPhaseModulator(?Audio\Signal\IStream $oModulator): self {
        $this->oPhaseModulator = $oModulator;
        $this->configureInputStage();
        return $this;
    }

    /**
     * @return Audio\Signal\IStream|null
     */
    public function getPhaseModulator(): ?Audio\Signal\IStream {
        return $this->oPhaseModulator;
    }

    /**
     *  Sets the overal Phase Modulation strength.
     *
     * @param  float $fModulationIndex
     * @return self
     */
    public function setPhaseModulationIndex(float $fModulationIndex): self {
        $this->fPhaseModulationIndex = $fModulationIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getPhaseModulationIndex(): float {
        return $this->fPhaseModulationIndex;
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
    public function setLevelModulator(?Audio\Signal\IStream $oModulator): self {
        $this->oLevelModulator = $oModulator;
        $this->configureOutputStage();
        return $this;
    }

    /**
     * @return Audio\Signal\IStream|null
     */
    public function getLevelModulator(): ?Audio\Signal\IStream {
        return $this->oLevelModulator;
    }

    /**
     *  Sets the overal Phase Modulation strength.
     *
     * @param  float $fModulationIndex
     * @return self
     */
    public function setLevelModulationIndex(float $fModulationIndex): self {
        $this->fLevelModulationIndex = $fModulationIndex;
        return $this;
    }

    /**
     * @return float
     */
    public function getLevelModulationIndex(): float {
        return $this->fLevelModulationIndex;
    }

    /**
     * Set the specific output envelope to use.
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        $this->oLevelEnvelope = $oEnvelope;
        $this->configureOutputStage();
        return $this;
    }

    /**
     * @return Audio\Signal\IEnvelope|null
     */
    public function getLevelEnvelope(): ?Audio\Signal\IEnvelope {
        return $this->oLevelEnvelope;
    }

    /**
     * Set the specific pitch envelope to use.
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        $this->oPitchEnvelope = $oEnvelope;
        $this->configureInputStage();
        return $this;
    }

    /**
     * @return Audio\Signal\IEnvelope|null
     */
    public function getPitchEnvelope(): ?Audio\Signal\IEnvelope {
        return $this->oPitchEnvelope;
    }

    public function setPhaseFeedbackIndex(float $fFeedback): self {
        $this->fPhaseFeedbackIndex = $fFeedback;
        $this->configureOutputStage();
        return $this;
    }

    /**
     * Calculates a new audio packet
     *
     * @return Audio\Signal\Packet;
     */
    protected function emitNew(): Audio\Signal\Packet {
        // Generate the waveform input. Sadly you can't call a member closure directly.
        $cInputStage = $this->cInputStage;
        $cInputStage();

        // Generate the waveform output. Sadly you can't call a member closure directly.
        $cOutputStage = $this->cOutputStage;
        $cOutputStage();

        if ($this->bAntialias) {
            /**
            * Apply a 5 sample travelling hamming window over the output
             */
            $fPrev1  = $this->fAAPrev1;
            $fPrev2  = $this->fAAPrev2;
            $fPrev3  = $this->fAAPrev3;
            $fPrev4  = $this->fAAPrev4;
            $oOutput  = clone $this->oLastOutput;
            foreach ($this->oLastOutput as $i => $fSample) {
                $oOutput[$i] = 0.1 * (
                    $fSample + $fPrev4 +
                    2.0 * ($fPrev1 + $fPrev3)
                    + 4.0 * $fPrev2
                );
                $fPrev4 = $fPrev3;
                $fPrev3 = $fPrev2;
                $fPrev2 = $fPrev1;
                $fPrev1 = $fSample;
            }
            $this->fAAPrev1 = $fPrev1;
            $this->fAAPrev2 = $fPrev2;
            $this->fAAPrev3 = $fPrev3;
            $this->fAAPrev4 = $fPrev4;
            $this->oLastOutput = $oOutput;
        }
        return $this->oLastOutput;
    }

    private function configureAntialias(): void {
        switch ($this->iAntialiasMode) {
            case self::ANTIALIAS_OFF:
                $this->bAntialias = true;
                break;
            case self::ANTIALIAS_ON:
                $this->bAntialias = true;
                break;
            case self::ANTIALIAS_AUTO:
                $this->bAntialias = $this->oWaveform instanceof Audio\Signal\Waveform\IHardTransient;
                break;
            default:
                break;
        }
    }

    private const
        // Variations on input stage
        INS_APERIODIC               = 0, // Waveform is aperiodic and has no phase or pitch (e.g. noise)
        INS_NO_MOD                  = 1, // Fixed frequency
        INS_PITCH_MOD               = 2, // Pitch LFO
        INS_PITCH_ENV               = 3, // Pitch Envelope
        INS_PITCH_MOD_ENV           = 4, // Pitch LFO + Pitch Envelope
        INS_PHASE_MOD               = 5, // Phase Modulation (FM)
        INS_PHASE_MOD_PITCH_MOD     = 6, // Phase Modulation (FM) + Pitch LFO
        INS_PHASE_MOD_PITCH_ENV     = 7, // Phase Modulation (FM) + Pitch Envelope
        INS_PHASE_MOD_PITCH_MOD_ENV = 8, // Phase Modulation (FM) + Pitch LFO + Pitch Envelope

        // Treat phase modulation indexes below a critical threshold as no phase modulation
        MIN_PHASE_MOD_INDEX         = 0.01,

        OUT_NO_MOD                  = 0,
        OUT_LEVEL_MOD               = 1,
        OUT_LEVEL_ENV               = 2,
        OUT_LEVEL_MOD_ENV           = 3,
        OUT_FEEDBACK                = 4,
        OUT_FEEDBACK_LEVEL_MOD      = 5,
        OUT_FEEDBACK_LEVEL_ENV      = 6,
        OUT_FEEDBACK_LEVEL_MOD_ENV  = 7,

        // Treat feedback modulation indexes below a critical threshold as no feedback modulation
        MIN_FEEDBACK_MOD_INDEX      = 0.01,
        FEEDBACK_SCALE              = 0.75,

        // Treat level modulation indexes below a critical threshold as no level modulation
        MIN_LEVEL_MOD_INDEX         = 0.01
    ;

    /**
     * Considers all the factors that affect how the waveform phase inputs are affected and chooses the
     * most appropriate closue function.
     */
    private function configureInputStage(): void {
        if ($this->bAperiodic) {
            $this->cInputStage = $this->aInputStages[self::INS_APERIODIC];
        } else {
            $iCase = self::INS_NO_MOD +
                ($this->oPitchModulator ? 1 : 0) |
                ($this->oPitchEnvelope  ? 2 : 0) |
                (
                    $this->oPhaseModulator &&
                    (self::MIN_PHASE_MOD_INDEX < $this->fPhaseModulationIndex) ? 4 : 0
                );
            $this->cInputStage = $this->aInputStages[$iCase];
        }
    }

    /**
     * Considers all the factors that affect how the waveform output is affected and chooses the
     * most appropriate closue function.
     */
    private function configureOutputStage(): void {
        $iCase =
            (
                $this->oLevelModulator &&
                (self::MIN_LEVEL_MOD_INDEX < $this->fLevelModulationIndex) ? 1 : 0
            ) |
            ($this->oLevelEnvelope  ? 2 : 0) |
            (
                (false == $this->bAperiodic &&
                self::MIN_FEEDBACK_MOD_INDEX < $this->fPhaseFeedbackIndex) ? 4 : 0
            );
        $this->cOutputStage = $this->aOutputStages[$iCase];
    }


    /**
     * Creates the set of input stage closures that calculate a packet of waveform phase inputs, taking
     * care of the factors that influence it.
     */
    private function createInputStages(): void {
        $this->aInputStages = [
            // For aperiodic waveforms, pitch and phase modulation are meaningless
            self::INS_APERIODIC => function(): void { },

            // If there are no pitch or phase modulators, the calculation is a linear function of fixed frequency
            self::INS_NO_MOD => function(): void {
                for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                    $this->oWaveformInput[$i] = $this->fScaleVal * $this->iSamplePosition++;
                }
            },

            // For a Pitch LFO, we have to apply the effect of the pitch change on frequency
            self::INS_PITCH_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = $this->oPitchModulator->emit($this->iLastIndex);
                $this->populatePitchShiftedPacket($oPitchShifts);
            },

            // For a Pitch Envelope, we have to apply the effect of the pitch change on frequency
            self::INS_PITCH_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = $this->oPitchEnvelope->emit($this->iLastIndex);
                $this->populatePitchShiftedPacket($oPitchShifts);
            },

            // For a Pitch Envelope and LFO, we have to apply the combined effect of the pitch change on frequency
            self::INS_PITCH_MOD_ENV => function(): void {
                // Effect is additive
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = clone $this->oPitchModulator->emit($this->iLastIndex);
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts->sumWith($this->oPitchEnvelope->emit($this->iLastIndex));
                $this->populatePitchShiftedPacket($oPitchShifts);
            },

            // For phase modulation we have to apply the effect on the instantaneous sample phase
            self::INS_PHASE_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPhaseShifts = $this->oPhaseModulator->emit($this->iLastIndex);
                $fPeriod = $this->fPhaseModulationIndex * $this->fWaveformPeriod;
                for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                    $this->oWaveformInput[$i] = ($this->fScaleVal * $this->iSamplePosition++) + $fPeriod * $oPhaseShifts[$i];
                }
            },

            // Combined effects of pitch and phase
            self::INS_PHASE_MOD_PITCH_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = $this->oPitchModulator->emit($this->iLastIndex);
                // @phpstan-ignore-next-line - member is never null in this context
                $oPhaseShifts = $this->oPhaseModulator->emit($this->iLastIndex);
                $this->populatePitchAndPhaseShiftedPacket($oPitchShifts, $oPhaseShifts);
            },

            // Combined effects of pitch and phase
            self::INS_PHASE_MOD_PITCH_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = $this->oPitchEnvelope->emit($this->iLastIndex);
                // @phpstan-ignore-next-line - member is never null in this context
                $oPhaseShifts = $this->oPhaseModulator->emit($this->iLastIndex);
                $this->populatePitchAndPhaseShiftedPacket($oPitchShifts, $oPhaseShifts);
            },

            // Combined effects of pitch and phase
            self::INS_PHASE_MOD_PITCH_MOD_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts = clone $this->oPitchModulator->emit($this->iLastIndex);
                // @phpstan-ignore-next-line - member is never null in this context
                $oPitchShifts->sumWith($this->oPitchEnvelope->emit($this->iLastIndex));
                // @phpstan-ignore-next-line - member is never null in this context
                $oPhaseShifts = $this->oPhaseModulator->emit($this->iLastIndex);
                $this->populatePitchAndPhaseShiftedPacket($oPitchShifts, $oPhaseShifts);
            }
        ];
        $this->configureInputStage();
    }

    /**
     * Common code from some of the input stage closures. Calculates the waveform input for cases where
     * there is a pitch modulation going on.
     */
    private function populatePitchShiftedPacket(Audio\Signal\Packet $oPitchShifts): void {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fNextFrequencyMultiplier = 2.0 ** ($oPitchShifts[$i] * self::INV_TWELVETH);
            $fNextFrequency           = $this->fFrequency * $fNextFrequencyMultiplier;
            $fTime                    = $this->fTimeStep  * $this->iSamplePosition++;
            $this->oWaveformInput[$i] = ($this->fCurrentFrequency * $fTime) + $this->fPhaseCorrection;
            $this->fPhaseCorrection   += $fTime * ($this->fCurrentFrequency - $fNextFrequency);
            $this->fCurrentFrequency  = $fNextFrequency;
        }
    }

    /**
     * Common code from some of the input stage closures. Calculates the waveform input for cases where
     * there is a pitch and phase modulation going on.
     */
    private function populatePitchAndPhaseShiftedPacket(
        Audio\Signal\Packet $oPitchShifts,
        Audio\Signal\Packet $oPhaseShifts
    ): void {
        $fPeriod = $this->fPhaseModulationIndex * $this->fWaveformPeriod;
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fNextFrequencyMultiplier = 2.0 ** ($oPitchShifts[$i] * self::INV_TWELVETH);
            $fNextFrequency           = $this->fFrequency * $fNextFrequencyMultiplier;
            $fTime                    = $this->fTimeStep  * $this->iSamplePosition++;
            $this->oWaveformInput[$i] = ($this->fCurrentFrequency * $fTime) + $this->fPhaseCorrection + $fPeriod * $oPhaseShifts[$i];
            $this->fPhaseCorrection   += $fTime * ($this->fCurrentFrequency - $fNextFrequency);
            $this->fCurrentFrequency  = $fNextFrequency;
        }
    }

    private function createOutputStages(): void {
        $this->aOutputStages = [
            // No modulation? Just map and go.
            self::OUT_NO_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $this->oLastOutput = $this->oWaveform->map($this->oWaveformInput);
            },

            // Level LFO
            self::OUT_LEVEL_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $this->oLastOutput = $this->oWaveform->map($this->oWaveformInput);

                // @phpstan-ignore-next-line - member is never null in this context
                $oOutputLevel = clone $this->oLevelModulator->emit($this->iLastIndex);
                $oOutputLevel->scaleBy($this->fLevelModulationIndex);

                $this->oLastOutput->modulateWith($oOutputLevel);
            },

            // Level Envelope
            self::OUT_LEVEL_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $this->oLastOutput = $this->oWaveform->map($this->oWaveformInput);
                $this->oLastOutput->modulateWith(
                    // @phpstan-ignore-next-line - member is never null in this context
                    $this->oLevelEnvelope->emit($this->iLastIndex)
                );
            },

            // Level LFO and Envelope, premultiplied
            self::OUT_LEVEL_MOD_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $this->oLastOutput = $this->oWaveform->map($this->oWaveformInput);

                // @phpstan-ignore-next-line - member is never null in this context
                $oOutputLevel = clone $this->oLevelModulator->emit($this->iLastIndex);
                $oOutputLevel->scaleBy($this->fLevelModulationIndex);
                $oOutputLevel->modulateWith(
                    // @phpstan-ignore-next-line - member is never null in this context
                    $this->oLevelEnvelope->emit($this->iLastIndex)
                );
                $this->oLastOutput->modulateWith($oOutputLevel);
            },

            // Feedback - this requires per sample calculation
            self::OUT_FEEDBACK => function(): void {
                $fIndex = $this->fPhaseFeedbackIndex * self::FEEDBACK_SCALE;
                for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
                    $this->oLastOutput[$i] = $fOutput = $this->oWaveform->value(
                        $this->oWaveformInput[$i] +
                        $fIndex * ($this->fFeedBack1 + $this->fFeedBack2)
                    );
                    $this->fFeedBack2 = $this->fFeedBack1;
                    $this->fFeedBack1 = $fOutput;
                }
            },

            self::OUT_FEEDBACK_LEVEL_MOD => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oOutputLevel = clone $this->oLevelModulator->emit($this->iLastIndex);
                $oOutputLevel->scaleBy($this->fLevelModulationIndex);
                $this->populateOutputPacketWithFeedback($oOutputLevel);
            },

            self::OUT_FEEDBACK_LEVEL_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oOutputLevel = $this->oLevelEnvelope->emit($this->iLastIndex);
                $this->populateOutputPacketWithFeedback($oOutputLevel);
            },

            self::OUT_FEEDBACK_LEVEL_MOD_ENV => function(): void {
                // @phpstan-ignore-next-line - member is never null in this context
                $oOutputLevel = clone $this->oLevelModulator->emit($this->iLastIndex);
                $oOutputLevel->scaleBy($this->fLevelModulationIndex);
                $oOutputLevel->modulateWith(
                    // @phpstan-ignore-next-line - member is never null in this context
                    $this->oLevelEnvelope->emit($this->iLastIndex)
                );
                $this->populateOutputPacketWithFeedback($oOutputLevel);
            },
        ];
        $this->configureOutputStage();
    }

    private function populateOutputPacketWithFeedback(Audio\Signal\Packet $oOutputLevel): void {
        $fIndex = $this->fPhaseFeedbackIndex * self::FEEDBACK_SCALE;
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this->oLastOutput[$i] = $fOutput = $this->oWaveform->value(
                $this->oWaveformInput[$i] +
                $fIndex * ($this->fFeedBack1 + $this->fFeedBack2)
            ) * $oOutputLevel[$i];
            $this->fFeedBack2 = $this->fFeedBack1;
            $this->fFeedBack1 = $fOutput;
        }
    }
}

