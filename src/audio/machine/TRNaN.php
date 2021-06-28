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

namespace ABadCafe\PDE\Audio\Machine;
use ABadCafe\PDE\Audio;

/**
 * TRNaN
 *
 * Basic analogish sounding drum machine
 */
class TRNaN implements Audio\IMachine {

    const
        KICK      = 0,
        SNARE     = 1,
        HH_CLOSED = 2,
        HH_OPEN   = 3,
        COWBELL   = 4,
        CLAP      = 5,
        TOM       = 6
    ;

    /**
     * These voices will mute
     */
    const MUTE_GROUPS = [
        self::HH_CLOSED => [self::HH_OPEN],
        self::HH_OPEN   => [self::HH_CLOSED]
    ];

    use TPolyphonicMachine, TSimpleVelocity, TControllerless;

    private $aVoices = [];

    public function __construct() {
        $this->initPolyphony(7);
        $this->aVoices[self::KICK]      = new Percussion\AnalogueKick();
        $this->aVoices[self::SNARE]     = new Percussion\AnalogueSnare();
        $this->aVoices[self::HH_CLOSED] = new Percussion\AnalogueHHClosed();
        $this->aVoices[self::HH_OPEN]   = new Percussion\AnalogueHHOpen();
        $this->aVoices[self::COWBELL]   = new Percussion\AnalogueCowbell();
        $this->aVoices[self::CLAP]      = new Percussion\AnalogueClap();
        $this->aVoices[self::TOM]       = new Percussion\AnalogueTom();
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->setVoiceSource($i, $this->aVoices[$i]->getOutputStream());
        }
    }

    /**
     * @inheritDoc
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName) : self {
        isset($this->aVoices[$iVoiceNumber]) &&
        $this->aVoices[$iVoiceNumber]->setNote($sNoteName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startVoice(int $iVoiceNumber) : self {
        if (isset(self::MUTE_GROUPS[$iVoiceNumber])) {
            foreach (self::MUTE_GROUPS[$iVoiceNumber] as $iMuteNumber) {
                $this->aVoices[$iMuteNumber]
                    ->getOutputStream()
                    ->disable();
            }
        }
        isset($this->aVoices[$iVoiceNumber]) &&
        $this->aVoices[$iVoiceNumber]
            ->getOutputStream()
            ->reset()
            ->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function stopVoice(int $iVoiceNumber) : self {
        isset($this->aVoices[$iVoiceNumber]) &&
        $this->aVoices[$iVoiceNumber]
            ->getOutputStream()
            ->disable();
        return $this;
    }
}
