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
 * WhyAYeSID
 *
 * Simple multivoice chip tune machine. Each voice has a basic oscillator with it's own waveform, vibrato, tremelo
 * and envelope settings. Basic waveforms common to 8-bit machines are supported.
 */
class WhyAYeSID implements Audio\IMachine {

    use TPolyphonicMachine, TSimpleVelocity, TAutomated;

    /**
     * @const int[] WAVE_TYPES
     */
    const WAVETABLE = [
        Audio\Signal\IWaveform::SINE,
        Audio\Signal\IWaveform::TRIANGLE,
        Audio\Signal\IWaveform::SAW,
        Audio\Signal\IWaveform::SINE_SAW_HARD,
        Audio\Signal\IWaveform::SQUARE,
        Audio\Signal\IWaveform::POKEY,
        Audio\Signal\IWaveform::PULSE
    ];

    private Audio\Signal\Oscillator\LFO $oPulseWidthModulator;

    /** @var Audio\Signal\IWaveform[] $aWaveforms */
    private array $aWaveforms = [];

    /** @var Audio\Signal\LevelAdjust<Audio\Signal\Oscillator\Sound>[] $aVoices */
    private array $aVoices = [];

    private int   $iVoiceMask;

    /**
     * Constructor. Sets the default polyphony level and allocates the various parts.
     *
     * @param int $iNumVoices
     */
    public function __construct(int $iNumVoices) {
        $this->initWaves();
        $this->initPolyphony($iNumVoices);
        $this->iVoiceMask = (1 << $this->iNumVoices) - 1;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aVoices[$i] = $oVoice = $this->createInitialVoice();
            $this->setVoiceSource($i, $oVoice);
        }
        $this->oPulseWidthModulator = new Audio\Signal\Oscillator\LFOZeroToOne(
            new Audio\Signal\Waveform\Sine(),
            1,
            0.75
        );
        $this->initAutomated();
    }

    /**
     * @inheritDoc
     */
    public function getControllerDefs(): array {
        return [
            new Control\Switcher(
                self::CTRL_OSC_1_WAVE,
                [$this, 'setVoiceWaveform'],
                Audio\Signal\IWaveform::SINE
            ),
            new Control\Knob(
                self::CTRL_VIBRATO_RATE,
                [$this, 'setVoiceVibratoRate'],
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_VIBRATO_DEPTH,
                [$this, 'setVoiceVibratoDepth'],
                0
            ),
            new Control\Knob(
                self::CTRL_TREMOLO_RATE,
                [$this, 'setVoiceTremoloRate'],
                0,
                self::CTRL_DEF_LFO_RATE_MIN,
                self::CTRL_DEF_LFO_RATE_MAX
            ),
            new Control\Knob(
                self::CTRL_TREMOLO_DEPTH,
                [$this, 'setVoiceTremoloDepth'],
                0
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getControllerNames(): array {
        return self::CTRL_NAMES;
    }

    public function setPulseWidth(float $fDuty): self {
        /** @var Audio\Signal\Waveform\Pulse $oWaveform */
        $oWaveform = $this->aWaveforms[Audio\Signal\IWaveform::PULSE];
        $oWaveform->setPulsewidth($fDuty);
        return $this;
    }

    public function enablePulseWidthLFO(): self {
        /** @var Audio\Signal\Waveform\Pulse $oWaveform */
        $oWaveform = $this->aWaveforms[Audio\Signal\IWaveform::PULSE];
        $oWaveform->setPulsewidthModulator($this->oPulseWidthModulator);
        return $this;
    }

    public function disablePulseWidthLFO(): self {
        /** @var Audio\Signal\Waveform\Pulse $oWaveform */
        $oWaveform = $this->aWaveforms[Audio\Signal\IWaveform::PULSE];
        $oWaveform->setPulsewidthModulator(null);
        return $this;
    }

    public function setPulseWidthLFORate(float $fRateHz): self {
        $this->oPulseWidthModulator->setFrequency($fRateHz);
        return $this;
    }

    public function setPulseWidthLFODepth(float $fDepth): self {
        $this->oPulseWidthModulator->setDepth($fDepth);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self {
        if (isset($this->aVoices[$iVoiceNumber])) {
            $fFrequency = Audio\Note::getFrequency($sNoteName);
            $this->aVoices[$iVoiceNumber]->getStream()->setFrequency($fFrequency);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceVelocity(int $iVoiceNumber, int $iVelocity): self {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber): self {
        if (isset($this->aVoices[$iVoiceNumber])) {
            $this->aVoices[$iVoiceNumber]
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
        if (isset($this->aVoices[$iVoiceNumber])) {
            $this->aVoices[$iVoiceNumber]->disable();
        }
        return $this;
    }

    public function setVoiceWaveform(int $iVoice, int $iWaveform): self {
        if (
            isset($this->aVoices[$iVoice]) &&
            isset($this->aWaveforms[$iWaveform])
        ) {
            $this->aVoices[$iVoice]->setLevel(Audio\Signal\IWaveform::ROOT_SPECTRAL_POWER[$iWaveform]);
            $this->aVoices[$iVoice]->getStream()->setWaveform($this->aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * Set the vibrato rate, in Hertz
     *
     * @param  int   $iVoice
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceVibratoRate(int $iVoice, float $fRateHz): self {
        if (isset($this->aVoices[$iVoice])) {
            /** @var Audio\Signal\Oscillator\LFO $oModulator */
            $oModulator = $this->aVoices[$iVoice]->getStream()->getPitchModulator();
            $oModulator->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the vibrato depth.
     *
     * @param  int   $iVoice
     * @param  float $fDepth
     * @return self
     */
    public function setVoiceVibratoDepth(int $iVoice, float $fDepth): self {
        if (isset($this->aVoices[$iVoice])) {
            /** @var Audio\Signal\Oscillator\LFO $oModulator */
            $oModulator = $this->aVoices[$iVoice]->getStream()->getPitchModulator();
            $oModulator->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the tremolo rate, in Hertz
     *
     * @param  int   $iVoice
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceTremoloRate(int $iVoice, float $fRateHz): self {
        if (isset($this->aVoices[$iVoice])) {
            /** @var Audio\Signal\Oscillator\LFO $oModulator */
            $oModulator = $this->aVoices[$iVoice]->getStream()->getLevelModulator();
            $oModulator->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the vibrato depth.
     *
     * @param  int   $iVoice
     * @param  float $fDepth
     * @return self
     */
    public function setVoiceTremoloDepth(int $iVoice, float $fDepth): self {
        if (isset($this->aVoices[$iVoice])) {
            /** @var Audio\Signal\Oscillator\LFO $oModulator */
            $oModulator = $this->aVoices[$iVoice]->getStream()->getLevelModulator();
            $oModulator->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the volume envelope to use dor a set of voices.
     *
     * @param  int $iVoiceMask
     * @param  Audio\Signal\IEnvelope $oEnvelope
     * @return self
     */
    public function setVoiceMaskEnvelope(int $iVoiceMask, Audio\Signal\IEnvelope $oEnvelope): self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->setLevelEnvelope($oEnvelope->share());
        }
        return $this;
    }

    /**
     * Create an initial voice for a voice. Defaults to a triangle waveform with a small 4Hz vibrato.
     *
     * @return Audio\Signal\LevelAdjust<Audio\Signal\Oscillator\Sound>
     */
    private function createInitialVoice(): Audio\Signal\LevelAdjust {
        $iDefaultWaveform = Audio\Signal\IWaveform::TRIANGLE;

        $oOscillator = new Audio\Signal\Oscillator\Sound($this->aWaveforms[$iDefaultWaveform]);
        $oOscillator->setPitchModulator(
            new Audio\Signal\Oscillator\LFO(
                new Audio\Signal\Waveform\Sine(),
                4.0,
                0.1
            )
        );
        $oOscillator->setLevelModulator(
            new Audio\Signal\Oscillator\LFOOneToZero(
                new Audio\Signal\Waveform\Sine(),
                2.0,
                0.1
            )
        );
        $oOscillator->setLevelEnvelope(
            new Audio\Signal\Envelope\Shape(
                0.0,
                [
                    [1.0, 0.01],
                    [0.75, 0.25],
                    [0.0, 10.0]
                ]
            )
        );
        $oLevelAdjust = new Audio\Signal\LevelAdjust(
            $oOscillator,
            Audio\Signal\IWaveform::ROOT_SPECTRAL_POWER[$iDefaultWaveform]
        );
        $oLevelAdjust->disable();
        return $oLevelAdjust;
    }

    /**
     * Returns an array of the selected voices implied by a voice mask.
     *
     * @param  int $iVoiceMask
     * @return Audio\Signal\LevelAdjust<Audio\Signal\Oscillator\Sound>[]
     */
    private function getSelectedVoices(int $iVoiceMask): array {
        $aResult = [];
        if ($iVoiceMask & $this->iVoiceMask) {
            $iVoice = $this->iNumVoices - 1;
            while ($iVoice >= 0) {
                if ($iVoiceMask & (1 << $iVoice)) {
                    $aResult[$iVoice] = $this->aVoices[$iVoice];
                }
                --$iVoice;
            }
        }
        return $aResult;
    }

    private function initWaves(): void {
        $this->aWaveforms = Audio\Signal\Waveform\Flyweight::get()
            ->getWaveforms(self::WAVETABLE);
    }
}
