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

namespace ABadCafe\PDE\Audio\Machine\Percussion;
use ABadCafe\PDE\Audio;

/**
 * AnalogueCowbell
 *
 * Cowbell made by slightly detuned pulswave generators with a filter and decay envelope.
 */
class AnalogueCowbell implements IVoice {

    const
        DEF_RATIO         = 869.4 / 587.3,
        CENTRE_FREQ       = 580,
        SEMI_SCALE        = 0.25 * Audio\Note::FACTOR_PER_SEMI,
        OSC_LO_DUTY_CYCLE = 0.43,
        OSC_HI_DUTY_CYCLE = 0.27,
        OSC_HI_PHASE_MOD  = 0.165,
        FILTER_CUTOFF     = 0.052,
        FILTER_RESONANCE  = 0.65
    ;

    private Audio\Signal\IOscillator     $oOscillator1, $oOscillator2;
    private Audio\Signal\IEnvelope       $oEnvelope;
    private Audio\Signal\IFilter         $oFilter;

    /** @var Audio\Signal\Operator\AutoMuteSilence<Audio\Signal\Filter\BandPass> $oAutoMute */
    private Audio\Signal\Operator\AutoMuteSilence $oAutoMute;

    /**
     * Constructor
     */
    public function __construct() {
        $this->oEnvelope = new Audio\Signal\Envelope\Shape(
            0.2, [
                [0.33, 0.001],
                [0.03, 0.1],
                [0.0, 0.5]
            ]
        );

        $fBase = self::CENTRE_FREQ;

        $this->oOscillator1 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Pulse(self::OSC_LO_DUTY_CYCLE),
            $fBase
        );
        $this->oOscillator2 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Pulse(self::OSC_HI_DUTY_CYCLE),
            $fBase * self::DEF_RATIO
        );
        $this->oOscillator1
            ->setAntialiasMode(Audio\Signal\Oscillator\Sound::ANTIALIAS_OFF)
            ->setLevelEnvelope($this->oEnvelope);
        $this->oOscillator2
            ->setAntialiasMode(Audio\Signal\Oscillator\Sound::ANTIALIAS_OFF)
            ->setLevelEnvelope($this->oEnvelope)
            ->setPhaseModulator($this->oOscillator1)
            ->setPhaseModulationIndex(self::OSC_HI_PHASE_MOD)
        ;
        $oMixer = new Audio\Signal\Operator\FixedMixer();
        $oMixer
            ->addInputStream('l', $this->oOscillator1, 1.0)
            ->addInputStream('h', $this->oOscillator2, 1.0)
            ->setOutputLevel(0.5);
        $this->oFilter = new Audio\Signal\Filter\BandPass(
            $oMixer,
            self::FILTER_CUTOFF,
            self::FILTER_RESONANCE
        );
        $this->oAutoMute = new Audio\Signal\Operator\AutoMuteSilence($this->oFilter, 0.03, 1/512.0);
        $this->oAutoMute->disable();
    }

    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote) - Audio\Note::CENTRE_REFERENCE;
        $fNote       = self::SEMI_SCALE * $iNoteNumber;
        $fBase       = self::CENTRE_FREQ * 2.0 ** $fNote;
        $this->oOscillator1->setFrequency($fBase);
        $this->oOscillator2->setFrequency($fBase * self::DEF_RATIO);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVelocity(int $iVelocity): self {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputStream(): Audio\Signal\IStream {
        return $this->oAutoMute;
    }

}
