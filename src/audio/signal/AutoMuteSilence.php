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
use function ABadCafe\PDE\dprintf;

/**
 * AutoMuteSilence
 *
 * Wrapper for another IStream that automatically disables itself when its input streams output sigal strength falls
 * below a given threshold for a certain time. Use as a gate to disable more expensive upstream signal sources.
 *
 * The threshold value is based on the full normalised signal scale of -1.0 to 1.0, so it is important to scale this
 * parameter if the input stream is operating at a different scaling (usually attenuated) in order to avoid muting
 * a signal prematurely.
 *
 * Note that input signals below the RMS threshold will not be muted provided they are rising, This prevents muting
 * anything with a long attack.
 */

/**
 * @template T of IStream
 */
class AutoMuteSilence implements IStream {

    /**
     * Default threshold for a full scale signal.
     */
    const
        DEF_THRESHOLD = 1.0/256.0,
        DEF_DURATION  = 0.05
    ;

    private const
        SAMPLE_DISTANCE = 16,
        SCALE_FACTOR    = self::SAMPLE_DISTANCE / Audio\IConfig::PACKET_SIZE
    ;

    use TStream;

    /** @var T $oStream */
    private IStream $oStream;

    private float $fThresholdSquared;
    private float $fLastTotalSquared = 0;

    private int
        $iSilentPacketLimit = 0,
        $iSilentPacketCount = 0
    ;

    /**
     * Constructor
     *
     * @param T       $oStream
     * @param float   $fSeconds   - How long the stresm output is below the threshold before muting
     * @param float   $fThreshold - Normalised RMS level below which a stream is considered silent
     */
    public function __construct(
        IStream $oStream,
        float $fSeconds = self::DEF_DURATION,
        float $fThreshold = self::DEF_THRESHOLD
    ) {
        self::initStreamTrait();
        $this->oStream = $oStream;
        $this->setThreshold($fThreshold);
        $this->setDisableAfter($fSeconds);
    }

    public function enable(): IStream {
        $this->bEnabled = true;
        $this->fLastTotalSquared  = 0.0;
        $this->iSilentPacketCount = 0;
        return $this;
    }


    /**
     * @param  float $fSeconds
     * @return self<T>
     */
    public function setDisableAfter(float $fSeconds): self {
        $this->iSilentPacketLimit = $fSeconds > 0.0 ? ((int)($fSeconds * Audio\IConfig::PROCESS_RATE / Audio\IConfig::PACKET_SIZE)) : 0;
        return $this;
    }

    /**
     * @param  float $fThreshold
     * @return self<T>
     */
    public function setThreshold(float $fThreshold): self {
        $this->fThresholdSquared = $fThreshold * $fThreshold;
        dprintf(
            "%s: Signal threshold set to %.4e RMS for %d consecutive packets\n",
            self::class,
            $fThreshold,
            $this->iSilentPacketLimit
        );
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
     *
     * @return self<T>
     */
    public function reset(): self {
        $this->oStream->reset();
        $this->enable();
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

            if ($fTotalSquared > $this->fLastTotalSquared) {
                // If the total is rising, keep the gate open
                $this->fLastTotalSquared  = $fTotalSquared;
                $this->iSilentPacketCount = 0;
            } else if ($fTotalSquared < $this->fThresholdSquared) {
                // If the total is not rising and is below the threshold, start closing the gate.
                if (++$this->iSilentPacketCount > $this->iSilentPacketLimit) {
                    $this->bEnabled          = false;
                    $this->fLastTotalSquared = 0.0;
                }
            } else {
                // If not rising but above the threshold, keep the gate open
                $this->iSilentPacketCount = 0;
            }
            return $oPacket;
        } else {
            return $this->emitSilence();
        }
    }

    /**
     * @param  T $oStream
     * @return self<T>
     */
    public function setStream(IStream $oStream): self {
        $this->oStream = $oStream;
        return $this;
    }

    /**
     * @return T
     */
    public function getStream(): IStream {
        return $this->oStream;
    }
}
