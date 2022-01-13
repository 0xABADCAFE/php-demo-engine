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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

/**
 * AutoMuteAfter
 *
 * Wrapper for another IStream that automatically disables itself when its input streams output sigal strength falls
 * below a given threshold for a certain time.
 */
class AutoMuteSilence implements IStream {

    const DEF_THRESHOLD = 0.001;

    private const
        SAMPLE_DISTANCE = 16,
        SCALE_FACTOR    = self::SAMPLE_DISTANCE / Audio\IConfig::PACKET_SIZE
    ;

    use TStream;

    private IStream $oStream;

    private float $fThresholdSquared;

    private int
        $iSilentPacketLimit = 0,
        $iSilentPacketCount = 0
    ;

    /**
     * Constructor
     *
     * @param IStream $oStream
     * @param float   $fSeconds   - How long the stresm output is below the threshold before muting
     * @param float   $fThreshold - Normalised RMS level below which a stream is considered silent
     */
    public function __construct(IStream $oStream, float $fSeconds, float $fThreshold = self::DEF_THRESHOLD) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setThreshold($fThreshold);
        $this->setDisableAfter($fSeconds);
    }

    /**
     * @param  float $fSeconds
     * @return self
     */
    public function setDisableAfter(float $fSeconds): self {
        $this->iSilentPacketLimit = $fSeconds > 0.0 ? ((int)($fSeconds * Audio\IConfig::PROCESS_RATE / Audio\IConfig::PACKET_SIZE)) : 0;
        echo "Packets before mute ", $this->iSilentPacketLimit, "\n";
        return $this;
    }

    public function setThreshold(float $fThreshold): self {
        $this->fThresholdSquared = $fThreshold * $fThreshold;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * Reports the stream position of the wrapped stream rather than the wrapper.
     */
    public function getPosition(): int {
        return $this->oStream->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Packet {
        if ($this->bEnabled) {
            $oPacket = $this->oStream->emit($iIndex);
            $fTotalSquared = 0.0;
            $iDistance = self::SAMPLE_DISTANCE;
            for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; $i += $iDistance) {
                $fSample = $oPacket[$i];
                $fTotalSquared += $fSample * $fSample;
            }
            $fTotalSquared *= self::SCALE_FACTOR;
            if ($fTotalSquared < $this->fThresholdSquared) {
                if (++$this->iSilentPacketCount > $this->iSilentPacketLimit) {
                    $this->bEnabled = false;
                }
            } else {
                $this->iSilentPacketCount = 0;
            }
            return $oPacket;
        } else {
            return $this->emitSilence();
        }
    }

    public function getStream(): IStream {
        return $this->oStream;
    }
}
