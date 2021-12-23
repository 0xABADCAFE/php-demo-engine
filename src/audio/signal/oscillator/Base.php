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

namespace ABadCafe\PDE\Audio\Signal\Oscillator;

use ABadCafe\PDE\Audio;

/**
 * Base Oscillator Class
 */
abstract class Base implements Audio\Signal\IOscillator {

    const MIN_FREQUENCY = 1.0;
    const DEF_FREQUENCY = 440.0;
    const MAX_FREQUENCY = 24000.0;

    use Audio\Signal\TPacketIndexAware, Audio\Signal\TStream;

    protected ?Audio\Signal\IWaveform $oWaveform = null;

    protected Audio\Signal\Packet
        $oWaveformInput,
        $oLastOutput;

    protected float
        $fFrequency        = 0.0,
        $fCurrentFrequency = 0.0,
        $fPhaseOffset      = 0.0,
        $fPhaseCorrection  = 0.0,
        $fWaveformPeriod   = 1.0,
        $fTimeStep         = 0.0,
        $fScaleVal         = 0.0
    ;

    protected int $iSamplePosition = 0;

    /**
     * Constructor
     *
     * @param Audio\Signal\IWaveform|null $oWaveform
     * @param float                       $fFrequency
     * @param float                       $fPhase
     */
    public function __construct(
        ?Audio\Signal\IWaveform $oWaveform = null,
        float $fFrequency = 0.0,
        float $fPhase     = 0.0
    ) {
        self::initStreamTrait();
        $fFrequency = $fFrequency <= 0.0 ? static::DEF_FREQUENCY : $fFrequency;
        $this->oWaveformInput = Audio\Signal\Packet::create();
        $this->oLastOutput    = Audio\Signal\Packet::create();
        $this->setWaveform($oWaveform);
        $this->setFrequency($fFrequency);
        $this->fPhaseOffset = $this->fPhaseCorrection = $fPhase;
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->iSamplePosition;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->iSamplePosition   = 0;
        $this->fCurrentFrequency = $this->fFrequency;
        $this->fPhaseCorrection  = $this->fPhaseOffset;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        if (!$this->bEnabled || null === $this->oWaveform) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oLastOutput;
        }
        return $this->emitNew();
    }

    /**
     * @inheritDoc
     */
    public function setWaveform(?Audio\Signal\IWaveform $oWaveform): self {
        if ($oWaveform) {
            $this->oWaveform       = clone $oWaveform;
            $this->fWaveformPeriod = $oWaveform->getPeriod();
            $this->fTimeStep       = $this->fWaveformPeriod * Audio\IConfig::SAMPLE_PERIOD;
            $this->fScaleVal = $this->fTimeStep * $this->fFrequency;
        } else {
            $this->oWaveform       = null;
            $this->fWaveformPeriod = 1.0;
            $this->fTimeStep       = $this->fWaveformPeriod * Audio\IConfig::SAMPLE_PERIOD;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFrequency(float $fFrequency): self {
        $fLastFrequency = $this->fCurrentFrequency;
        $this->fCurrentFrequency =
        $this->fFrequency        = ($fFrequency < static::MIN_FREQUENCY) ?
            static::MIN_FREQUENCY : (
                ($fFrequency > static::MAX_FREQUENCY) ?
                    static::MAX_FREQUENCY :
                    $fFrequency
            );
        $this->fScaleVal = $this->fTimeStep * $this->fFrequency;
        return $this;
    }

    /**
     * Emit a new signal packet.
     *
     * @return Audio\Signal\Packet
     */
    protected abstract function emitNew(): Audio\Signal\Packet;
}

