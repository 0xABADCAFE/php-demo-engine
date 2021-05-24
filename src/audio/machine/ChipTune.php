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
 * Simple multichannel chip tune machine. Each channel has a basic oscillator with it's own waveform, vibrato, tremelo
 * and envelope settings.
 */
class ChipTune implements Audio\IMachine {

    const
        SINE     = 0,
        TRIANGLE = 1,
        SAW      = 2,
        SQUARE   = 3,
        PULSE    = 4
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
            $this->oMixer->addStream('voice_' . $i, $this->aVoices[$i], $fDefaultMixLevel);
        }
    }

    /**
     * @inheritDoc
     */
    public function noteOn(string $sNoteName, int $iVelocity, int $iChannel) : self {
        if (isset($this->aVoices[$iChannel])) {
            $fFrequency = Audio\Note::getFrequency($sNoteName);

            $this->aVoices[$iChannel]
                ->reset()
                ->enable()
                ->setFrequency($fFrequency);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function noteOff(int $iChannel) : self {
        if (isset($this->aVoices[$iChannel])) {
            $this->aVoices[$iChannel]->disable();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->oMixer->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->oMixer->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        return $this->oMixer->emit($iIndex);
    }

    /**
     * Set the waveform type to use for a set of channels.
     *
     * @param  int  $iChannelMask
     * @param  int  $iWaveform
     * @return self
     */
    public function setChannelWaveform(int $iChannelMask, int $iWaveform) : self {
        if (isset(self::$aWaveforms[$iWaveform])) {
            $oWaveform = self::$aWaveforms[$iWaveform];
            $aVoices   = $this->getSelectedVoices($iChannelMask);
            foreach ($aVoices as $oVoice) {
                $oVoice->setWaveform($oWaveform);
            }
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
        $oOscillator = new Audio\Signal\Oscillator\Sound(new Audio\Signal\Waveform\Triangle());
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
        $oOscillator->setEnvelope(
            new Audio\Signal\Envelope\Shape( // TODO - define an adjustable ASDR
                0.0,
                [
                    [1.0, 0.01],
                    [0.75, 0.25],
                    [0.0, 10.0]
                ]
            )
        );
        $oOscillator->disable();
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
                --$iChannel;
            }
        }
        return $aResult;
    }

    private static function initShared() {
        if (empty(self::$aWaveforms)) {
            self::$aWaveforms = [
                self::SINE     => new Audio\Signal\Waveform\Sine(),
                self::TRIANGLE => new Audio\Signal\Waveform\Triangle(),
                self::SAW      => new Audio\Signal\Waveform\Saw(),
                self::SQUARE   => new Audio\Signal\Waveform\Square(),
                self::PULSE    => new Audio\Signal\Waveform\Pulse(),
            ];
        }
    }
}
