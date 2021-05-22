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

/**
 * ChipTune
 *
 * Simple multichannel chip tune machine. Each channel has a basic oscillator with it's own waveform, vibrato, tremelo
 * and envelope settings.
 */
class ChipTune implements Audio\IMachine {

    const
        SINE     = 0,
        TRIANGLE = 1,
        SQUARE   = 2,
        PULSE    = 3
    ;

    private static array $aWaveforms = [];
    private int          $iPolyphony;
    private int          $iChannelMask;
    private array        $aVoices = [];
    private Audio\Signal\FixedMixer $oMixer;

    /**
     * Constructor. Sets the default polyphony level and allocates the various parts.
     *
     * @param int $iPolyphony
     */
    public function __construct(int $iPolyphony) {
        self::initShared();
        $this->iPolyphony   = max(min($iPolyphony, self::MAX_POLYPHONY), self::MIN_POLYPHONY);
        $this->iChannelMask = (1 << $this->iPolyphony) - 1;
        $this->oMixer       = new Audio\Signal\FixedMixer;
        $fDefaultMixLevel   = 1.0 / $this->iPolyphony;
        for ($i = 0; $i < $this->iPolyphony; ++$i) {
            $this->aVoices[$i] = $this->createInitialVoice();
            $this->oMixer->addStream('voice_' . $i, $this->oVoices[$i], $fDefaultMixLevel);
        }
    }

    /**
     * Set the waveform type to use for a set of channels.
     *
     * @param  int  $iChannelMask
     * @param  int  $iWaveform
     * @return self
     * @throws OutOfBoundsException
     */
    public function setChannelWaveform(int $iChannelMask, int $iWaveform) : self {
        if (!isset(self::$aWaveforms[$iWaveform])) {
            throw new \OutOfBoundsException();
        }
        $oWaveform = self::$aWaveforms[$iWaveform];
        $aVoices   = $this->getSelectedVoices($iChannelMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->setWaveform($oWaveform);
        }
        return $this;
    }

    /**
     * Set the vibrato rate, in Hertz, to use for a set of channels.
     *
     * @param  int   $iChannelMask
     * @param  float $fRateHz
     * @return self
     */
    public function setChannelVibratoRate(int $iChannelMask, float $fRateHz) : self {
        $aVoices = $this->getSelectedVoices($iChannelMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getPitchModulator()->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the vibrato depth, in semitones, to use for a set of channels.
     *
     * @param  int   $iChannelMask
     * @param  float $fRateHz
     * @return self
     */
    public function setChannelVibratoDepth(int $iChannelMask, float $fDepth) : self {
        $aVoices = $this->getSelectedVoices($iChannelMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getPitchModulator()->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Set the tremelo rate, in Hertz, to use for a set of channels.
     *
     * @param  int   $iChannelMask
     * @param  float $fRateHz
     * @return self
     */
    public function setChannelTremeloRate(int $iChannelMask, float $fRateHz) : self {
        $aVoices = $this->getSelectedVoices($iChannelMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getLevelModulator()->setFrequency($fRateHz);
        }
        return $this;
    }

    /**
     * Set the tremelo depth, in semitones, to use for a set of channels.
     *
     * @param  int   $iChannelMask
     * @param  float $fRateHz
     * @return self
     */
    public function setChannelTremeloDepth(int $iChannelMask, float $fDepth) : self {
        $aVoices = $this->getSelectedVoices($iChannelMask);
        foreach ($aVoices as $oVoice) {
            $oVoice->getPitchModulator()->setDepth($fDepth);
        }
        return $this;
    }

    /**
     * Create an initial voice for a channel. Defaults to a triangle waveform with a small 4Hz vibrato.
     */
    private function createInitialVoice() : Audio\Signal\Oscillator\Sound {
        $oOscillator = new Audio\Signal\Oscillator\Sound(Audio\Signal\Waveform\Triangle());
        $oOscillator->setPitchModulator(
            new Audio\Signal\Waveform\Sine(),
            4.0,
            0.1
        );
        $oOscillator->setLevelModulator(
            new Audio\Signal\Waveform\Sine(),
            4.0,
            0.0
        );
        $oOscillator->setEnvelope(
            new Audio\Signal\Envelope\Shape( // todo - define an adjustable ASDR
                0.0,
                [
                    [1.0, 0.01],
                    [0.75, 0.25],
                    [0.0, 10.0]
                ]
            )
        );
        return $oOscillator;
    }

    /**
     * Returns an array of the selected voices implied by a channel mask.
     *
     * @param  int $iChannelMask
     * @return Audio\Signal\Oscillator\Sound[]
     */
    private function getSelectedVoices(int $iChannelMask) : array {
        $aResult = [];
        if ($iChannelMask & $this->iChannelMask) {
            $iChannel = $this->iPolyphony - 1;
            while ($iChannel >= 0) {
                if ($iChannelMask & (1 << $iChannel)) {
                    $aResult[$iChannel] = $this->aVoices[$iChannel];
                }
            }
        }
        return $aResult;
    }

    private static function initShared() {
        if (empty(self::$aWaveforms)) {
            self::$aWaveforms = [
                self::SINE     => new Audio\Signal\Waveform\Sine(),
                self::TRIANGLE => new Audio\Signal\Waveform\Triangle(),
                self::SQUARE   => new Audio\Signal\Waveform\Square(),
                self::PULSE    => new Audio\Signal\Waveform\Pulse(),
            ];
        }
    }
}
