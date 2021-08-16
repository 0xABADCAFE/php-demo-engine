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
 * TPolyphonicMachine
 *
 * Manages the common aspects of maintaining a number of discrete voices.
 */
trait TPolyphonicMachine {

    use Audio\Signal\TStream;

    protected int $iNumVoices = 1;

    private   Audio\Signal\IStream $oOutput;

    protected Audio\Signal\FixedMixer $oMixer;
    protected ?Audio\Signal\IInsert   $oInsert = null;
    protected float
        $fOutScale = 1.0,
        $fOutLevel = 1.0
    ;

    /**
     * Initialise the components of this trait.
     *
     * @param int $iNumVoices
     */
    protected function initPolyphony(int $iNumVoices) : void {
        self::initStreamTrait();
        $this->iNumVoices = \max(\min($iNumVoices, Audio\IMachine::MAX_POLYPHONY), Audio\IMachine::MIN_POLYPHONY);
        $this->fOutScale  = $this->fOutLevel / $this->iNumVoices;
        $this->oOutput    =
        $this->oMixer     = new Audio\Signal\FixedMixer();
        $this->setOutputLevel($this->fOutLevel);
    }

    /**
     * Set the voice source for a given channel.
     *
     * @param int                  $iVoiceNumber
     * @param Audio\Signal\IStream $oStream
     * @param float                $fLevel
     */
    protected function setVoiceSource(int $iVoiceNumber, Audio\Signal\IStream $oStream, float $fLevel = 1.0) : void {
        $this->oMixer->addInputStream('v_' . $iVoiceNumber, $oStream, $fLevel);
    }

    /**
     * @inheritDoc
     */
    public function getNumVoices() : int {
        return $this->iNumVoices;
    }

    /**
     * @inheritDoc
     */
    public function getVoiceLevel(int $iVoiceNumber) : float {
        $sVoiceName = 'v_' . $iVoiceNumber;
        return $this->oMixer->getInputLevel($sVoiceName);
    }

    /**
     * @inheritDoc
     */
    public function setVoiceLevel(int $iVoiceNumber, float $fVolume) : Audio\IMachine {
        $sVoiceName = 'v_' . $iVoiceNumber;
        $this->oMixer->setInputLevel($sVoiceName, $fVolume);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputLevel() : float {
        return $this->fOutLevel;
    }

    /**
     * @inheritDoc
     */
    public function setOutputLevel(float $fVolume) : Audio\IMachine {
        $this->fOutLevel = $fVolume;
        $this->oMixer->setOutputLevel($this->fOutLevel * $this->fOutScale);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->Output->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset() : Audio\Signal\IStream {
        $this->oOutput->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        return $this->oOutput->emit($iIndex);
    }

    /**
     * @inheritDoc
     */
    public function getInsert() : ?Audio\Signal\IInsert  {
        return $this->oInsert;
    }

    /**
     * @inheritDoc
     */
    public function setInsert(?Audio\Signal\IInsert $oInsert = null) : self {
        if ($this->oInsert = $oInsert) {
            $oInsert->setInputStream($this->oMixer);
            $this->oOutput = $oInsert;
        } else {
            $this->oOutput = $this->oMixer;
        }
        return $this;
    }
}
