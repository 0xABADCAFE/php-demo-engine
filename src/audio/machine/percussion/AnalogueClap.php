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
     * Variation by octave - first value is the amplitude scaling, second is the time scaling.
     */
    const OCTAVE = [
         0 => [1.33, 2.5],
         1 => [1.33, 2.0],
         2 => [1.33, 1.5],
         3 => [1.33, 1.25],
         4 => [1.33, 1],
         5 => [1.33, 0.9],
         6 => [1.33, 0.8],
         7 => [1.2, 0.7],
         8 => [1.15, 0.6],
         9 => [1.125, 0.55],
        10 => [1.00, 0.5],
    ];

    /**
     * Variation by semitone, affects the filter cutoff
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

    public function __construct() {
        parent::__construct();
        // Use a clappier envelope here. Stack 4 very short decays followed by a short tail off.
        $this->oVolumeEnv = new Audio\Signal\Envelope\Shape(
            0.5,
            [
                [0.1, 0.005],

                [0.6, 0.001],
                [0.15, 0.006],

                [0.8, 0.001],
                [0.2, 0.007],

                [0.8, 0.001],
                [0.15, 0.008],

                [1.0, 0.001],
                [0.2, 0.05],
                [0.1, 0.05],
                [0.0, 0.05]
            ]
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

        $this->oFilter->setCutoff(self::SEMITONE[$iSemitone][0]);

        $this->oVolumeEnv
            ->setLevelScale(self::OCTAVE[$iOctave][0])
            ->setTimeScale(self::OCTAVE[$iOctave][1])
        ;
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
            ->setCutoff(0.09)
            ->setResonance(0.3);
    }
}
