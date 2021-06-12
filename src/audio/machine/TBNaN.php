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
 * Monophonic bassline. Oscillator with filter.
 */
class TBNaN implements Audio\IMachine {

    const LEVEL_ADJUST = [
        Audio\Signal\IWaveform::SAW      => 0.33,
        Audio\Signal\IWaveform::SQUARE   => 0.25,
        Audio\Signal\IWaveform::PULSE    => 0.25
    ];

    use TMonophonicMachine;

    private array $aWaveforms = [];

    private Audio\Signal\Oscillator\Sound $oOscillator;
    private Audio\Signal\Oscillator\LFO   $oPWM;
    private Audio\Signal\IFilter          $oFilter;
    private Audio\Signal\IEnvelope        $oFEG, $oAEG;

    public function __construct() {
        $this->initWaveforms();
        $this->initOscillator();
        $this->initFilter();
        $this->setVoiceSource($this->oFilter, 0.125);
        $this->oVoice->disable();
    }

    /**
     * Set the cutoff limit for the LPF
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setCutoff(float $fCutoff) : self {
        $this->oFilter->setCutoff($fCutoff);
        return $this;
    }

    /**
     * Set the resonance limit for the LPF
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setResonance(float $fResonance) : self {
        $this->oFilter->setResonance($fResonance);
        return $this;
    }

    /**
     * Set the amplitude decay
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setLevelDecay(float $fHalfLife) : self {
        $this->oAEG->setHalfLife($fHalfLife);
        return $this;
    }

    /**
     * Set the amplitude decay
     *
     * @param  float $fCutoff
     * @return self
     */
    public function setCutoffDecay(float $fHalfLife) : self {
        $this->oFEG->setHalfLife($fHalfLife);
        return $this;
    }

    /**
     * Set the waveform type
     *
     * @param  int $iWaveform
     * @return self
     */
    public function setWaveform(int $iWaveform) : self {
        if (isset($this->aWaveforms[$iWaveform])) {
            $this->oOscillator->setWaveform($this->aWaveforms[$iWaveform]);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName) : self {
        $this->oOscillator->setFrequency(Audio\Note::getFrequency($sNoteName));
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber) : self {
        $this->oVoice
            ->reset()
            ->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber) : self {
        $this->oVoice->disable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    private function initWaveforms() {
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
        $this->aWaveforms[self::PULSE]->setPulsewidthModulator($this->oPWM);
    }

    private function initOscillator() {
        $this->oAEG = new Audio\Signal\Envelope\DecayPulse(
            0.8,
            0.07
        );
        $this->oOscillator = new Audio\Signal\Oscillator\Sound($this->aWaveforms[self::PULSE]);
        $this->oOscillator->setLevelEnvelope($this->oAEG);
    }

    private function initFilter() {
        $this->oFEG = new Audio\Signal\Envelope\DecayPulse(
            0.33,
            0.05
        );
        $this->oFilter = new Audio\Signal\Filter\LowPass(
            $this->oOscillator,
            1.0,
            0.7,
            $this->oFEG
        );
    }
}


