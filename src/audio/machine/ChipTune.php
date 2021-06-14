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
 * ChipTune
 *
 * Simple multivoice chip tune machine. Each voice has a basic oscillator with it's own waveform, vibrato, tremelo
 * and envelope settings.
 */
class ChipTune implements Audio\IMachine {

    use TPolyphonicMachine;

    const LEVEL_ADJUST = [
        Audio\Signal\IWaveform::SINE     => 1.0,
        Audio\Signal\IWaveform::TRIANGLE => 0.9,
        Audio\Signal\IWaveform::SAW      => 0.33,
        Audio\Signal\IWaveform::SQUARE   => 0.25,
        Audio\Signal\IWaveform::PULSE    => 0.25
    ];

    private static array $aWaveforms = [];
    private int          $iVoiceMask;
    private array        $aVoices = [];

    /**
     * Constructor. Sets the default polyphony level and allocates the various parts.
     *
     * @param int $iNumVoices
     */
    public function __construct(int $iNumVoices) {
        self::initShared();
        $this->initPolyphony($iNumVoices);
        $this->iVoiceMask = (1 << $this->iNumVoices) - 1;
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->aVoices[$i] = $oVoice = $this->createInitialVoice();
            $this->setVoiceSource($i, $oVoice);
        }
    }

    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName) : self {
        if (isset($this->aVoices[$iVoiceNumber])) {
            $fFrequency = Audio\Note::getFrequency($sNoteName);
            $this->aVoices[$iVoiceNumber]->getStream()->setFrequency($fFrequency);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceVelocity(int $iVoiceNumber, int $iVelocity) : self {

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber) : self {
        if (isset($this->aVoices[$iVoiceNumber])) {
            $this->aVoices[$iVoiceNumber]
                ->reset()
                ->enable();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber) : self {
        if (isset($this->aVoices[$iVoiceNumber])) {
            $this->aVoices[$iVoiceNumber]->disable();
        }
        return $this;
    }

    /**
     * Set the waveform type to use for a set of voices.
     *
     * @param  int  $iVoiceMask
     * @param  int  $iWaveform
     * @return self
     */
    public function setVoiceMaskWaveform(int $iVoiceMask, int $iWaveform) : self {
        if (isset(self::$aWaveforms[$iWaveform])) {
            $oWaveform = self::$aWaveforms[$iWaveform];
            $aVoices   = $this->getSelectedVoices($iVoiceMask);
            foreach ($aVoices as $oVoice) {
                $oVoice->setLevel(self::LEVEL_ADJUST[$iWaveform]);
                $oVoice->getStream()->setWaveform($oWaveform);
            }
        }
        return $this;
    }

    /**
     * Set the vibrato rate, in Hertz, to use for a set of voices.
     *
     * @param  int   $iVoiceMask
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceMaskVibratoRate(int $iVoiceMask, float $fRateHz) : self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->getPitchModulator()->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the vibrato depth, in semitones, to use for a set of voices.
     *
     * @param  int   $iVoiceMask
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceMaskVibratoDepth(int $iVoiceMask, float $fDepth) : self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->getPitchModulator()->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the tremelo rate, in Hertz, to use for a set of voices.
     *
     * @param  int   $iVoiceMask
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceMaskTremeloRate(int $iVoiceMask, float $fRateHz) : self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->getLevelModulator()->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the tremelo depth, in semitones, to use for a set of voices.
     *
     * @param  int   $iVoiceMask
     * @param  float $fRateHz
     * @return self
     */
    public function setVoiceMaskTremeloDepth(int $iVoiceMask, float $fDepth) : self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->getPitchModulator()->setDepth($fDepth);
        }
        return $this;
    }

    public function setVoiceMaskEnvelope(int $iVoiceMask, Audio\Signal\IEnvelope $oEnvelope) : self {
        $aVoices = $this->getSelectedVoices($iVoiceMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getStream()->setLevelEnvelope(clone $oEnvelope);
        }
        return $this;
    }

    /**
     * Create an initial voice for a voice. Defaults to a triangle waveform with a small 4Hz vibrato.
     */
    private function createInitialVoice() : Audio\Signal\IStream {
        $iDefaultWaveform = Audio\Signal\IWaveform::TRIANGLE;

        $oOscillator = new Audio\Signal\Oscillator\Sound(self::$aWaveforms[$iDefaultWaveform]);
        $oOscillator->setPitchModulator(
            new Audio\Signal\Oscillator\LFO(
                new Audio\Signal\Waveform\Sine(),
                4.0,
                0.1
            )
        );
        $oOscillator->setLevelModulator(
            new Audio\Signal\Oscillator\LFOZeroToOne(
                new Audio\Signal\Waveform\Sine(),
                4.0,
                0.0
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
        $oLevelAdjust = new Audio\Signal\LevelAdjust($oOscillator, self::LEVEL_ADJUST[$iDefaultWaveform]);
        $oLevelAdjust->disable();
        return $oLevelAdjust;
    }

    /**
     * Returns an array of the selected voices implied by a voice mask.
     *
     * @param  int $iVoiceMask
     * @return Audio\Signal\Oscillator\Sound[]
     */
    private function getSelectedVoices(int $iVoiceMask) : array {
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

    private static function initShared() {
        if (empty(self::$aWaveforms)) {
            self::$aWaveforms = [
                Audio\Signal\IWaveform::SINE     => new Audio\Signal\Waveform\Sine(),
                Audio\Signal\IWaveform::TRIANGLE => new Audio\Signal\Waveform\Triangle(),
                Audio\Signal\IWaveform::SAW      => new Audio\Signal\Waveform\Saw(),
                Audio\Signal\IWaveform::SQUARE   => new Audio\Signal\Waveform\Square(),
                Audio\Signal\IWaveform::PULSE    => new Audio\Signal\Waveform\Pulse(),
            ];
        }
    }
}
