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
 * AnalogueSnare
 */
class AnalogueSnare implements IVoice {

    const DEF_RATIO = 339.0 / 185.0;

    const OCTAVE = [
         0 => [],
         1 => [],
         2 => [],
         3 => [],
         4 => [],
         5 => [],
         6 => [],
         7 => [],
         8 => [],
         9 => [],
        10 => [],
    ];

    const SEMITONE = [
         0 => [],
         1 => [],
         2 => [],
         3 => [],
         4 => [],
         5 => [],
         6 => [],
         7 => [],
         8 => [],
         9 => [],
        10 => [],
        11 => [],
    ];

    private Audio\Signal\IOscillator $oNoise, $oOscillator1, $oOscillator2;
    private Audio\Signal\IEnvelope   $oVolumeEnv;
    private Audio\Signal\IStream     $oAutoMute;

    /**
     * Constructor
     */
    public function __construct() {
        $fBase  = 170.0;

        $this->oNoise = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise
        );
        $this->oOscillator1 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            $fBase
        );
        $this->oOscillator2 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            $fBase * self::DEF_RATIO
        );
        $this->oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.8,
            0.025
        );

        $oMixer = new Audio\Signal\FixedMixer();
        $oMixer
            ->addInputStream('l', $this->oOscillator1, 0.9)
            ->addInputStream('h', $this->oOscillator2, 0.3)
            ->addInputStream('n', $this->oNoise, 1.0);
        $oVCA = new Audio\Signal\Modulator($oMixer, $this->oVolumeEnv);

        $this->oAutoMute = new Audio\Signal\AutoMuteAfter($oVCA, 0.3);

        $this->oAutoMute->disable();
    }


    /**
     * @inheritDoc
     */
    public function setNote(string $sNote) : self {
        $iNoteNumber = Audio\Note::getNumber($sNote);
        $iSemitone   = $iNoteNumber % Audio\Note::SEMIS_PER_OCTAVE;
        $iOctave     = (int)($iNoteNumber / Audio\Note::SEMIS_PER_OCTAVE);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setVelocity(int $iVelocity) : self {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputStream() : Audio\Signal\IStream {
        return $this->oAutoMute;
    }

}


