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
 * AnalogueClap
 *
 * Handclap made of bandpass filtered, shaped nosie.
 */
class AnalogueClap extends BandPassNoise {

    /**
     * Variation by octave
     */
    const OCTAVE = [
         0 => [1.33, 0.045],
         1 => [1.33, 0.04],
         2 => [1.33, 0.0375],
         3 => [1.33, 0.035],
         4 => [1.33, 0.0325],
         5 => [1.33, 0.03],
         6 => [1.33, 0.0275],
         7 => [1.2, 0.025],
         8 => [1.15, 0.0225],
         9 => [1.125, 0.02],
        10 => [1.00, 0.0175],
    ];

    /**
     * Variation by semitone
     */
    const SEMITONE = [
         0 => [0.06],
         1 => [0.0625],
         2 => [0.065],
         3 => [0.0675],
         4 => [0.07],
         5 => [0.0725],
         6 => [0.075],
         7 => [0.0775],
         8 => [0.08],
         9 => [0.09],
        10 => [0.1],
        11 => [0.12],
    ];

    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote);
        $iSemitone   = $iNoteNumber % Audio\Note::SEMIS_PER_OCTAVE;
        $iOctave     = (int)($iNoteNumber / Audio\Note::SEMIS_PER_OCTAVE);

        $this->oFilter->setCutoff(self::SEMITONE[$iSemitone][0]);
        $this->oVolumeEnv
            ->setInitial(self::OCTAVE[$iOctave][0])
            ->setHalfLife(self::OCTAVE[$iOctave][1]);

        $this->oAutoMute
            ->setDisableAfter(7.0 * self::OCTAVE[$iOctave][1]);
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
    protected function setDefaults() {
        $this->oFilter
            ->setCutoff(0.09)
            ->setResonance(0.1);
        $this->oVolumeEnv
            ->setInitial(1.33)
            ->setHalfLife(0.03);
        $this->oAutoMute
            ->setDisableAfter(0.175);
    }
}
