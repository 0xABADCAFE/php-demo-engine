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
 * AnalogueHHOpen
 */
class AnalogueHHOpen extends BandPassNoise {

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
    protected function setDefaults() {
        $this->oFilter
            ->setCutoff(0.53)
            ->setResonance(0.60);
        $this->oVolumeEnv
            ->setInitial(0.7)
            ->setHalfLife(0.05);
        $this->oAutoMute
            ->setDisableAfter(0.5);
    }

}



