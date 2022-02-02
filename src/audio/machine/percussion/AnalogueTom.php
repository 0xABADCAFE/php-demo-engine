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
 * AnalogueTom
 *
 * Simple tom sound made from sine wave. More or less the same as the kick but with different pitch range
 * and dynamics.
 */
class AnalogueTom implements IVoice {

    // 3 octave range, semitone tuned.
    const
        DEF_RANGE = 3 * Audio\Note::SEMIS_PER_OCTAVE,
        DEF_TRANS = 2 * Audio\Note::SEMIS_PER_OCTAVE,
        CENTRE_HZ = 110.0
    ;

    const DEF_IMPACT = 0.7;

    private Audio\Signal\IOscillator $oOscillator;
    private Audio\Signal\IEnvelope   $oVolumeEnv, $oPitchEnv;
    private Audio\Signal\IStream     $oAutoMute;

    /**
     * Constructor
     */
    public function __construct() {
        $this->oOscillator = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            self::CENTRE_HZ
        );
        $this->oPitchEnv = new Audio\Signal\Envelope\DecayPulse(
            2.0,
            0.07
        );
        $this->oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            self::DEF_IMPACT,
            0.175
        );
        $this->oOscillator
            ->setPitchEnvelope($this->oPitchEnv)
            ->setLevelEnvelope($this->oVolumeEnv);

        $this->oAutoMute = new Audio\Signal\Operator\AutoMuteAfter($this->oOscillator, 1.5);
        $this->oAutoMute->disable();
    }

    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote) + self::DEF_TRANS;
        $iSemitone   = ($iNoteNumber % self::DEF_RANGE) - Audio\Note::SEMIS_PER_OCTAVE;
        $fPower      = ($iSemitone) * Audio\Note::FACTOR_PER_SEMI;
        $fFrequency  = self::CENTRE_HZ * (2.0 ** $fPower);
        $this->oOscillator->setFrequency($fFrequency);
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
