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
        DEF_RATIO   = 869.4 / 587.3,
        CENTRE_FREQ = 580,
        SEMI_SCALE  = 0.25 * Audio\Note::FACTOR_PER_SEMI
    ;

    private Audio\Signal\IOscillator $oOscillator1, $oOscillator2;
    private Audio\Signal\IEnvelope   $oEnvelope;
    private Audio\Signal\IFilter     $oFilter;
    private Audio\Signal\IStream     $oAutoMute;

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
            new Audio\Signal\Waveform\AliasedPulse(0.43),
            $fBase
        );
        $this->oOscillator2 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\AliasedPulse(0.27),
            $fBase * self::DEF_RATIO
        );
        $this->oOscillator1->setLevelEnvelope($this->oEnvelope);
        $this->oOscillator2
            ->setLevelEnvelope($this->oEnvelope)
            ->setPhaseModulator($this->oOscillator1)
            ->setPhaseModulationIndex(0.165)
        ;
        $oMixer = new Audio\Signal\FixedMixer();
        $oMixer
            ->addInputStream('l', $this->oOscillator1, 1.0)
            ->addInputStream('h', $this->oOscillator2, 1.0)
            ->setOutputLevel(0.5);
        $this->oFilter = new Audio\Signal\Filter\BandPass(
            $oMixer,
            0.052,
            0.65
        );
        $this->oAutoMute = new Audio\Signal\AutoMuteAfter($this->oFilter, 0.6);
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
