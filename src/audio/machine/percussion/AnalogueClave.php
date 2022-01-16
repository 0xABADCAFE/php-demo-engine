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
 * AnalogueClave
 *
 * Clave sound made from a bandpassed triangle
 */
class AnalogueClave implements IVoice {

    const
        CENTRE_FREQ       = 2650,
        SEMI_SCALE        = 0.25 * Audio\Note::FACTOR_PER_SEMI,
        FILTER_CUTOFF     = 0.275,
        FILTER_RESONANCE  = 0.2
    ;

    private Audio\Signal\IOscillator     $oOscillator;
    private Audio\Signal\IEnvelope       $oEnvelope;
    private Audio\Signal\IFilter         $oFilter;

    /** @var Audio\Signal\AutoMuteSilence<Audio\Signal\Filter\BandPass> $oAutoMute */
    private Audio\Signal\AutoMuteSilence $oAutoMute;

    /**
     * Constructor
     */
    public function __construct() {
        $this->oEnvelope = new Audio\Signal\Envelope\DecayPulse(1.0, 0.007);

        $fBase = self::CENTRE_FREQ;

        $this->oOscillator = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Triangle(),
            $fBase
        );
        $this->oOscillator->setLevelEnvelope($this->oEnvelope);
        $this->oFilter = new Audio\Signal\Filter\BandPass(
            $this->oOscillator,
            self::FILTER_CUTOFF,
            self::FILTER_RESONANCE
        );
        $this->oAutoMute = new Audio\Signal\AutoMuteSilence($this->oFilter, 0.02, 1.0/256.0);
        $this->oAutoMute->disable();
    }

    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote) - Audio\Note::CENTRE_REFERENCE;
        $fNote       = self::SEMI_SCALE * $iNoteNumber;
        $fBase       = self::CENTRE_FREQ * 2.0 ** $fNote;
        $this->oOscillator->setFrequency($fBase);
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
