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
use function \max, \min;

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
    protected ?Audio\Signal\IInsert   $oInsert   = null;
    protected float                   $fOutLevel = 1.0;

    /** @var Audio\Signal\AutoMuteSilence<Audio\Signal\IStream> $oGate */
    protected Audio\Signal\AutoMuteSilence $oGate; // @phpstan-ignore-line

    /**
     * Initialise the components of this trait.
     *
     * @param int $iNumVoices
     */
    protected function initPolyphony(int $iNumVoices): void {
        self::initStreamTrait();
        $this->iNumVoices = max(min($iNumVoices, Audio\IMachine::MAX_POLYPHONY), Audio\IMachine::MIN_POLYPHONY);
        $this->oOutput    =
        $this->oMixer     = new Audio\Signal\FixedMixer();
        $this->oGate = new Audio\Signal\AutoMuteSilence($this->getOutput());
        $this->setOutputLevel($this->fOutLevel);
    }

    /**
     * Set the voice source for a given channel.
     *
     * @param int                  $iVoiceNumber
     * @param Audio\Signal\IStream $oStream
     * @param float                $fLevel
     */
    protected function setVoiceSource(int $iVoiceNumber, Audio\Signal\IStream $oStream, float $fLevel = 1.0): void {
        $this->oMixer->addInputStream('v_' . $iVoiceNumber, $oStream, $fLevel);
    }

    /**
     * @inheritDoc
     */
    public function getNumVoices(): int {
        return $this->iNumVoices;
    }

    /**
     * @inheritDoc
     */
    public function getVoiceLevel(int $iVoiceNumber): float {
        $sVoiceName = 'v_' . $iVoiceNumber;
        return $this->oMixer->getInputLevel($sVoiceName);
    }

    /**
     * @inheritDoc
     */
    public function setVoiceLevel(int $iVoiceNumber, float $fVolume): Audio\IMachine {
        $sVoiceName = 'v_' . $iVoiceNumber;
        $this->oMixer->setInputLevel($sVoiceName, $fVolume);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOutputLevel(): float {
        return $this->fOutLevel;
    }

    /**
     * @inheritDoc
     */
    public function setOutputLevel(float $fVolume): Audio\IMachine {
        $this->fOutLevel = $fVolume;
        $fMixLevel = $this->fOutLevel * Audio\IMachine::VOICE_ATTENUATE;
        $this->oMixer->setOutputLevel($fMixLevel);
        $this->oGate->setThreshold($fMixLevel * Audio\Signal\AutoMuteSilence::DEF_THRESHOLD);
        return $this;
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
        $this->oGate->enable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        return $this->oGate->emit();
    }

    /**
     * @inheritDoc
     */
    public function getInsert(): ?Audio\Signal\IInsert  {
        return $this->oInsert;
    }

    /**
     * @inheritDoc
     */
    public function setInsert(?Audio\Signal\IInsert $oInsert = null): self {
        $oOldOutput = $this->oOutput;
        if (null !== $oInsert) {
            $oInsert->setInputStream($this->oMixer);
            $this->oInsert =
            $this->oOutput = $oInsert;
        } else {
            $this->oOutput = $this->oMixer;
        }
        if ($oOldOutput !== $this->oOutput) {
            $this->oGate->setStream($this->getOutput());
        }
        return $this;
    }

    private function handleVoiceStarted(): void {
        $this->oGate->enable();
    }

    /**
     * Workaround for phpstan. Ensures only an abstract handle is returned for adding to the gate.
     */
    private function getOutput(): Audio\Signal\IStream {
        return $this->oOutput;
    }
}
