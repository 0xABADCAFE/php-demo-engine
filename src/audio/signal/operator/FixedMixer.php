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

namespace ABadCafe\PDE\Audio\Signal\Operator;
use ABadCafe\PDE\Audio;

/**
 * Fixed level mixer for multiple streams.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class FixedMixer implements Audio\Signal\IStream {

    use Audio\Signal\TStream, Audio\Signal\TPacketIndexAware;

    private int    $iPosition = 0;

    /** @var array<string, Audio\Signal\IStream> $aStreams */
    private array  $aStreams  = [];

    /** @var array<string, float> $aLevels */
    private array  $aLevels   = [];

    private float  $fOutLevel = 1.0;
    private Audio\Signal\Packet $oLastPacket;

    /**
     * Constructor
     *
     * @param float $fOutLevel
     */
    public function __construct(float $fOutLevel = 1.0) {
        self::initStreamTrait();
        $this->oLastPacket = Audio\Signal\Packet::create();
        $this->fOutLevel   = $fOutLevel;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->iPosition;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->iPosition  = 0;
        $this->iLastIndex = 0;
        $this->oLastPacket->fillWith(0);
        foreach ($this->aStreams as $oStream) {
            $oStream->reset();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;
        if (empty($this->aLevels) || !$this->bEnabled) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oLastPacket;
        }
        return $this->emitNew();
    }

    /**
     * Modify the level for a named input.
     *
     * @param  string $sInputName
     * @param  float  $fLevel
     * @return self
     */
    public function setInputLevel(string $sInputName, float $fLevel): self {
        $this->aLevels[$sInputName]  = $fLevel;
        return $this;
    }

    /**
     * Get the level for a named input. Returns zero for any unrecognised input name.
     *
     * @param  string $sInputName
     * @return float
     */
    public function getInputLevel(string $sInputName): float {
        return $this->aLevels[$sInputName] ?? 0.0;
    }

    /**
     * Get the mixed output level.
     *
     * @return float
     */
    public function getOutputLevel(): float {
        return $this->fOutLevel;
    }

    /**
     * Modify the mixed output level.
     *
     * @param  float $fOutLevel
     * @return self
     */
    public function setOutputLevel(float $fOutLevel): self {
        $this->fOutLevel = $fOutLevel;
        return $this;
    }

    /**
     * Adds a named stream, overwriting any existing stream of the same name,
     *
     * @param  string  $sInputName
     * @param  Audio\Signal\IStream $oStream
     * @param  float   $fLevel
     * @return self
     */
    public function addInputStream(string $sInputName, Audio\Signal\IStream $oStream, float $fLevel): self {
        $this->aStreams[$sInputName] = $oStream;
        $this->aLevels[$sInputName]  = $fLevel;
        return $this;
    }

    /**
     * Remove a named stream
     *
     * @param  string $sInputName
     * @return self
     */
    public function removeInputStream(string $sInputName): self {
        unset($this->aStreams[$sInputName]);
        unset($this->aLevels[$sInputName]);
        return $this;
    }

    /**
     * @return Audio\Signal\Packet
     */
    private function emitNew(): Audio\Signal\Packet {
        $this->oLastPacket->fillWith(0.0);
        foreach ($this->aStreams as $sInputName => $oStream) {
            if ($oStream->isEnabled()) {
                $this->oLastPacket->accumulate(
                    $oStream->emit($this->iLastIndex),
                    $this->aLevels[$sInputName]
                );
            }
        }
        return $this->oLastPacket->scaleBy($this->fOutLevel);
    }
}
