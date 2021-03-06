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
use function \array_column, \array_fill, \max, \min;

/**
 * DeX7er (pron Dexter)
 *
 * Polyphonic FM synth with 2 to 8 operators per voice. Matrix style modulation rather than fixed algorithm. It's
 * a... bloody murder... to program.
 *
 * Uses the FM\Operator type as the basic operator unit.
 */
class DeX7er implements Audio\IMachine {

    const
        MIN_OPERATORS = 2,
        DEF_OPERATORS = 4,
        MAX_OPERATORS = 8
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

    use TPolyphonicMachine, TControllerless;

    /** @var Audio\Signal\IWaveform[] $aWaveforms */
    private static array $aWaveforms = [];

    // One each per voice

    /** @var Audio\Signal\Operator\FixedMixer[] $aVoice */
    private array $aVoice = [];

    /** @var float[] $aBaseFreq */
    private array $aBaseFreq = [];

    /** @var FM\Operator[][] $aOperators - first index is operator number, second is voice */
    private array $aOperators = [];

    /** @var array<string, int> $aOperatorNames */
    private array $aOperatorNames = [];

    private int  $iNumOperators;

    private ?int $iUsingOperator = null;

    /**
     * Constructor
     *
     * @param int $iNumVoices    - Number of voices (polyphony)
     * @para, int $iNumOperators - How many operators per voice.
     */
    public function __construct(int $iNumVoices, int $iNumOperators = self::DEF_OPERATORS) {
        self::initShared();
        $this->initPolyphony($iNumVoices);
        $fMixLevel = 0.5 / $this->iNumVoices;
        $this->iNumOperators = max(min($iNumOperators, self::MAX_OPERATORS), self::MIN_OPERATORS);
        /** @var FM\Operator[]*/
        $aVoice = [];
        $this->aOperators = array_fill(0, $this->iNumOperators, $aVoice);

        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aBaseFreq[$i] = Audio\Note::CENTRE_FREQUENCY;
            $oVoiceMixer         = new Audio\Signal\Operator\FixedMixer();
            $this->aVoice[$i]    = $oVoiceMixer;
            for ($j = 0; $j < $this->iNumOperators; ++$j) {
                $oOperator = new FM\Operator;
                $this->aOperators[$j][$i] = $oOperator;
            }
            $oVoiceMixer
                ->addInputStream('op_0', $this->aOperators[0][$i], 1.0)
                ->disable()
            ;
            $this->setVoiceSource($i, $oVoiceMixer, $fMixLevel);
        }
    }

    /**
     * Set a custom alias for an operator number.
     *
     * @param  int    $iOperator
     * @param  string $sName
     * @return self
     */
    public function aliasOperator(int $iOperator, string $sName): self {
        if (!empty($sName) && isset($this->aOperators[$iOperator])) {
            $this->aOperatorNames[$sName] = $iOperator;
        }
        return $this;
    }

    /**
     * Select the enumerated operator to modify
     *
     * @param  int $iOperator
     * @return self
     */
    public function selectOperator(int $iOperator): self {
        $this->iUsingOperator = isset($this->aOperators[$iOperator]) ? $iOperator : null;
        return $this;
    }

    /**
     * Select the aliased operator to modify
     *
     * @param  string $sName
     * @return self
     */
    public function selectOperatorName(string $sName) {
        $this->iUsingOperator = $this->aOperatorNames[$sName] ?? null;
        return $this;
    }

    /**
     * Select a standard enumerated waveform for the operator
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setEnumeratedWaveform(int $iWaveform): self {
        if (null !== $this->iUsingOperator && isset(self::$aWaveforms[$iWaveform])) {
            $oWaveform = self::$aWaveforms[$iWaveform];
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setWaveform($oWaveform);
            }
        }
        return $this;
    }

    /**
     * Set a custom waveform for the operator
     *
     * @param  Audio\Signal\IWaveform $oWaveform
     * @return self
     */
    public function setWaveform(Audio\Signal\IWaveform $oWaveform): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setWaveform($oWaveform);
            }
        }
        return $this;
    }

    /**
     * Set the absolute frequency ratio for the selected Operator
     *
     * @param  float $fRatio
     * @return self
     */
    public function setRatio(float $fRatio): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setRatio($fRatio);
            }
        }
        return $this;
    }

    /**
     * Set the relative frequency ratio in semitones for the selected Operator
     *
     * @param  float $fSemitones
     * @return self
     */
    public function setRatioSemitones(float $fSemitones): self {
        if (null !== $this->iUsingOperator) {
            $this->setRatio(2.0 ** ($fSemitones * Audio\Note::FACTOR_PER_SEMI));
        }
        return $this;
    }

    /**
     * Set the operator level envelope
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setLevelEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setLevelEnvelope($oEnvelope ? clone $oEnvelope : null);
            }
        }
        return $this;
    }

    public function setLevelIntensityVelocityCurve(?Audio\IControlCurve $oCurve): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setLevelIntensityVelocityCurve($oCurve);
            }
        }
        return $this;
    }

    public function setLevelRateVelocityCurve(?Audio\IControlCurve $oCurve): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setLevelRateVelocityCurve($oCurve);
            }
        }
        return $this;
    }

    /**
     * Set the operator pitch envelope
     *
     * @param  Audio\Signal\IEnvelope|null $oEnvelope
     * @return self
     */
    public function setPitchEnvelope(?Audio\Signal\IEnvelope $oEnvelope): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setPitchEnvelope($oEnvelope ? clone $oEnvelope : null);
            }
        }
        return $this;
    }


    /**
     * Set the mix to output level of the selected operator. All operators can mix to the output regardless of
     * their role as a carrier or modulator.
     *
     * @param  float $fLevel
     * @return self
     */
    public function setOutputMixLevel(float $fLevel): self {
        if (null !== $this->iUsingOperator) {
            $sMixId = 'op_' . $this->iUsingOperator;
            if ($fLevel <= 0.0) {
                foreach ($this->aVoice as $oVoiceMixer) {
                    $oVoiceMixer->removeInputStream($sMixId);
                }
            } else {
                $aOperators = $this->aOperators[$this->iUsingOperator];
                foreach ($this->aVoice as $iVoice => $oVoiceMixer) {
                    $oVoiceMixer->addInputStream($sMixId, $aOperators[$iVoice], $fLevel);
                }
            }
        }
        return $this;
    }

    /**
     * Set the absolute frequency ratio for the selected Operator
     *
     * @param  float $fFeedback
     * @return self
     */
    public function setFeedbackIndex(float $fFeedback): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setFeedbackIndex($fFeedback);
            }
        }
        return $this;
    }

    /**
     * Set a modulator for the selected operator. Silently swallows illegal configurations.
     *
     * @param  int   $iModulator
     * @param  float $fModulationIndex
     * @return self
     */
    public function setModulation(int $iModulator, float $fModulationIndex): self {
        if (
            null === $this->iUsingOperator ||
            $this->iUsingOperator === $iModulator ||
            !isset($this->aOperators[$iModulator])
        ) {
            return $this;
        }
        $aCarriers   = $this->aOperators[$this->iUsingOperator];
        $aModulators = $this->aOperators[$iModulator];
        foreach ($aCarriers as $iVoice => $oCarrier) {
            try {
                $oCarrier->addModulator($aModulators[$iVoice], $fModulationIndex);
            } catch (\LogicException $oError) {
                // shsssh
            }
        }
        return $this;
    }

    /**
     * @param  float $fDepth
     * @return self
     */
    public function setPitchLFODepth($fDepth): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setPitchLFODepth($fDepth);
            }
        }
        return $this;
    }

    /**
     * @param  float $fRate
     * @return self
     */
    public function setPitchLFORate($fRate): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setPitchLFORate($fRate);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function enablePitchLFO(): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->enablePitchLFO();
            }
        }

        return $this;
    }

    /**
     * @return self
     */
    public function disablePitchLFO(): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->disablePitchLFO();
            }
        }
        return $this;
    }

    /**
     * @param  float $fDepth
     * @return self
     */
    public function setLevelLFODepth($fDepth): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setLevelLFODepth($fDepth);
            }
        }
        return $this;
    }

    /**
     * @param  float $fRate
     * @return self
     */
    public function setLevelLFORate($fRate): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->setLevelLFORate($fRate);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function enableLevelLFO(): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->enableLevelLFO();
            }
        }

        return $this;
    }

    /**
     * @return self
     */
    public function disableLevelLFO(): self {
        if (null !== $this->iUsingOperator) {
            foreach ($this->aOperators[$this->iUsingOperator] as $iVoice => $oOperator) {
                $oOperator->disableLevelLFO();
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $this->aBaseFreq[$iVoiceNumber] = $fFrequency = Audio\Note::getFrequency($sNoteName);
            $aOperators = array_column($this->aOperators, $iVoiceNumber);
            foreach ($aOperators as $oOperator) {
                $oOperator->setFrequency($fFrequency);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceVelocity(int $iVoiceNumber, int $iVelocity): self {
        if (isset($this->aVoice[$iVoiceNumber])) {
            $aOperators = array_column($this->aOperators, $iVoiceNumber);
            foreach ($aOperators as $oOperator) {
                $oOperator->setVelocity($iVelocity);
            }
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


    private static function initShared(): void {
        if (empty(self::$aWaveforms)) {
            self::$aWaveforms = Audio\Signal\Waveform\Flyweight::get()
            ->getWaveforms(self::WAVETABLE);
        }
    }
}
