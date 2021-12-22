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
 * Manages the common aspects of single voiced machines.
 */
trait TMonophonicMachine {

    use Audio\Signal\TStream;

    private   Audio\Signal\IStream     $oOutput;
    protected Audio\Signal\LevelAdjust $oVoice;
    protected ?Audio\Signal\IInsert    $oInsert = null;

    protected float
        $fAttenuation = 0.1,
        $fVoiceLevel  = 1.0
    ;

    /**
     * Set the source stream for the internal machine voice. The level parameter is normally used to
     * to attenuate the output.
     *
     * @param Audio\Signal\IStream $oVoice
     * @param float                $fLevel
     */
    protected function setVoiceSource(Audio\Signal\IStream $oVoice, float $fLevel): void {
        $this->fAttenuation = $fLevel;
        $this->oOutput =
        $this->oVoice  = new Audio\Signal\LevelAdjust($oVoice, $this->fLevel);
    }

    /**
     * @inheritDoc
     */
    public function getNumVoices(): int {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function getVoiceLevel(int $iVoiceNumber): float {
        return $this->fVoiceLevel;
    }

    /**
     * @inheritDoc
     */
    public function setVoiceLevel(int $iVoiceNumber, float $fVolume): Audio\IMachine {
        $this->fVoiceLevel = $fVolume;
        $this->oVoice->setLevel($fVolume * $this->fAttenuation);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputLevel(): float {
        return $this->fVoiceLevel;
    }

    /**
     * @inheritDoc
     */
    public function setOutputLevel(float $fVolume): Audio\IMachine {
        return $this->setVoiceLevel(0, $fVolume);
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->oOutput->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset(): Audio\Signal\IStream {
        $this->oOutput->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        return $this->oOutput->emit($iIndex);
    }

    /**
     * @inheritDoc
     */
    public function getInsert(): ?Audio\Signal\IInsert {
        return $this->oInsert;
    }

    /**
     * @inheritDoc
     */
    public function setInsert(?Audio\Signal\IInsert $oInsert = null): self {
        if ($this->oInsert = $oInsert) {
            $oInsert->setInputStream($this->oVoice);
            $this->oOutput = $oInsert;
        } else {
            $this->oOutput = $this->oVoice;
        }
        return $this;
    }
}
