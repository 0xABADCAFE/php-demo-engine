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

namespace ABadCafe\PDE\Audio\Signal\Insert;
use ABadCafe\PDE\Audio;
use function \exp;
/**
 * Distortion
 *
 */
class Distortion implements Audio\Signal\IInsert {

    use Audio\Signal\TStream;

    private const DRIVE_SCALE = 100.0;

    private ?Audio\Signal\IStream $oStream;

    private float
        $fDryLevel,
        $fDrive,
        $fPostAmp
    ;

    /**
     * Constructor
     *
     * @param Audio\Signal\IStream|null $oStream
     * @param float $fDryLevel
     * @param float $fDrive
     */
    public function __construct(
        ?Audio\Signal\IStream $oStream = null,
        float $fDryLevel               = 0.5,
        float $fDrive                  = 1.0,
        float $fPostAmp                = 1.0
    ) {
        self::initStreamTrait();
        $this->oStream    = $oStream;
        $this->fDryLevel  = $fDryLevel;
        $this->fDrive     = $fDrive;
        $this->fPostAmp   = $fPostAmp;
    }

    /**
     * @return float
     */
    public function getDrive(): float {
        return $this->fDryLevel;
    }

    /**
     * @param float $fDrive
     * @return self
     */
    public function setDrive(float $fDrive): self {
        $this->fDrive = $fDrive;
        return $this;
    }

    /**
     * @return float
     */
    public function getPostAmp(): float {
        return $this->fPostAmp;
    }

    /**
     * @param float $fDrive
     * @return self
     */
    public function setPostAmp(float $fPostAmp): self {
        $this->fPostAmp = $fPostAmp;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function getInputStream(): ?Audio\Signal\IStream {
        return $this->oStream;
    }

    /**
     * @inheritDoc
     */
    public function setInputStream(?Audio\Signal\IStream $oStream): self {
        $this->oStream = $oStream;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDryLevel(): float {
        return $this->fDryLevel;
    }

    /**
     * @inheritDoc
     */
    public function setDryLevel(float $fDryLevel): self {
        $this->fDryLevel = $fDryLevel;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->oStream ? $this->oStream->getPosition() : 0;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        if ($this->oStream) {
            $this->oStream->reset();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        if ($this->oStream && $this->bEnabled) {

            $fPreBoost = self::DRIVE_SCALE * $this->fDrive;
            $fPostAttn = $this->fPostAmp / $fPreBoost;
            // Get the dry signal
            $oDry = $this->oStream->emit($iIndex);

            $oDistorted = clone $oDry;
            foreach ($oDistorted as $i => $fValue) {
                $fValue *= $fPreBoost;
                $oDistorted[$i] = $fPostAttn * ($fValue > 0.0 ? (1.0 - exp(-$fValue)) : (exp($fValue) - 1.0));
            }

            return $oDry
                ->scaleBy($this->fDryLevel)
                ->sumWith($oDistorted);

        } else {
            return $this->emitSilence();
        }
    }

}
