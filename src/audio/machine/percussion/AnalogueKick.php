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
 * AnalogueKick
 *
 * Simple Kick made from sine wave with pitch and intensity decay envelope.
 */
class AnalogueKick implements IVoice {

    const DEF_IMPACT = 0.7;

    /**
     * Basic pitch and decay time are spread over the entire set of octaves. Decay patterns repeat every 5 octaves.
     */
    const DECAY_TABLE_BY_OCTAVE = [
         0 => [30.0, 0.5],
         1 => [34.0, 0.4],
         2 => [36.0, 0.3],
         3 => [40.0, 0.25],
         4 => [44.0, 0.225],
         5 => [48.0, 0.2],
         6 => [50.0, 0.175],
         7 => [52.0, 0.15],
         8 => [55.0, 0.13],
         9 => [57.5, 0.12],
        10 => [60.0, 0.1],
    ];

    /**
     * Pitch drop repeats twice per octave. Lower half octave has longer decay.
     */
    const PUNCH_TABLE_BY_SEMITONE = [
         0 => [5.0, 0.095],
         1 => [8.0, 0.09],
         2 => [13.0, 0.085],
         3 => [18.0, 0.0825],
         4 => [22.0, 0.08],
         5 => [26.0, 0.070],
         6 => [5.0, 0.08],
         7 => [8.0, 0.075],
         8 => [13.0, 0.0725],
         9 => [18.0, 0.07],
        10 => [22.0, 0.06],
        11 => [26.0, 0.05],
    ];

    private Audio\Signal\IOscillator $oOscillator;
    private Audio\Signal\Envelope\DecayPulse $oVolumeEnv, $oPitchEnv;

    /** @var Audio\Signal\Operator\AutoMuteSilence<Audio\Signal\Oscillator\Sound> $oAutoMute */
    private Audio\Signal\Operator\AutoMuteSilence $oAutoMute;

    /**
     * Constructor
     */
    public function __construct() {
        $this->oOscillator = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            48.0
        );
        $this->oPitchEnv = new Audio\Signal\Envelope\DecayPulse(
            18.0,
            0.07
        );
        $this->oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            self::DEF_IMPACT,
            0.2
        );
        $this->oOscillator
            ->setPitchEnvelope($this->oPitchEnv)
            ->setLevelEnvelope($this->oVolumeEnv);

        $this->oAutoMute = new Audio\Signal\Operator\AutoMuteSilence($this->oOscillator, 0.05, 0.1);
        $this->oAutoMute->disable();
    }


    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote);
        $iSemitone   = $iNoteNumber % Audio\Note::SEMIS_PER_OCTAVE;
        $iOctave     = (int)($iNoteNumber / Audio\Note::SEMIS_PER_OCTAVE);
        $this->oPitchEnv
            ->setInitial(self::PUNCH_TABLE_BY_SEMITONE[$iSemitone][0])
            ->setHalfLife(self::PUNCH_TABLE_BY_SEMITONE[$iSemitone][1]);
        $this->oOscillator->setFrequency(self::DECAY_TABLE_BY_OCTAVE[$iOctave][0]);
        $this->oVolumeEnv->setHalfLife(self::DECAY_TABLE_BY_OCTAVE[$iOctave][1]);
        $this->oAutoMute->setDisableAfter(self::DECAY_TABLE_BY_OCTAVE[$iOctave][1] * 6.0);
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
