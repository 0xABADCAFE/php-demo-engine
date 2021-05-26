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
 * TMonophonicMachine
 *
 * Manages the common aspects of single voiced machines
 */
trait TMonophonicMachine {

    use Audio\Signal\TStream;

    protected Audio\Signal\LevelAdjust $oVoice;

    protected ?Audio\Signal\IStream    $oInsert = null;

    protected function setVoiceSource(Audio\Signal\IStream $oVoice, float $fInitialLevel = 1.0) {
        $this->oVoice = new Audio\Signal\LevelAdjust($oVoice, $fInitialLevel);
    }

    public function getNumVoices() : int {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getVoiceLevel(int $iVoiceNumber) : float {
        return $this->oVoice->getLevel();
    }

    /**
     * @inheritDoc
     */
    public function setVoiceLevel(int $iVoiceNumber, float $fVolume) : Audio\IMachine {
        $this->oVoice->setLevel($fVolume);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputLevel() : float {
        return $this->oVoice->getLevel();
    }

    /**
     * @inheritDoc
     */
    public function setOutputLevel(float $fVolume) : Audio\IMachine {
        $this->oVoice->setLevel($fVolume);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->oVoice->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset() : Audio\Signal\IStream {
        $this->oVoice->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        return $this->oInsert ? $this->oInsert->emit($iIndex) : $this->oVoice->emit($iIndex);
    }

    public function setInsert(?Audio\Signal\IStream $oInsert = null) : self {
        if ($this->oInsert = $oInsert) {
            $oInsert->setStream($this->oVoice);
        }
        return $this;
    }
}
