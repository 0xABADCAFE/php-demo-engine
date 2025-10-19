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
 *
 * Simple open hi-hat made from filtered, shaped noise.
 ^
 * @extends BandPassNoise<Audio\Signal\Envelope\DecayPulse>
 */
class AnalogueHHOpen extends BandPassNoise {

    public function __construct() {
        parent::__construct();
        $this->oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.7,
            0.05
        );
        $this->oNoise->setLevelEnvelope($this->oVolumeEnv);
    }

    /**
     * @inheritDoc
     */
    public function setNote(string $sNote): self {
        $iNoteNumber = Audio\Note::getNumber($sNote);
        $iSemitone   = $iNoteNumber % Audio\Note::SEMIS_PER_OCTAVE;
        $iOctave     = (int)($iNoteNumber / Audio\Note::SEMIS_PER_OCTAVE);
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
    protected function setDefaults(): void {
        $this->oFilter
            ->setCutoff(0.53)
            ->setResonance(0.60);
    }
}
