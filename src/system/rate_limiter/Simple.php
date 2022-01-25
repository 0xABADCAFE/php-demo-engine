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

namespace ABadCafe\PDE\System\RateLimiter;
use ABadCafe\PDE\System;
use function \microtime, \printf, \time_sleep_until;

/**
 * Simple rate limiter.
 */
class Simple implements System\IRateLimiter {

    private int $iMaxFramesPerSecond, $iFrameNumber = 0;

    private float $fFirst, $fFrameDuration, $fTotalSlept = 0.0;

    /**
     * @inheritDoc
     */
    public function __construct(int $iMaxFramesPerSecond) {
        if ($iMaxFramesPerSecond < self::MIN_FPS_LIMIT || $iMaxFramesPerSecond > self::MAX_FPS_LIMIT) {
            throw new \RangeException();
        }
        $this->iMaxFramesPerSecond = $iMaxFramesPerSecond;
        $this->fFrameDuration      = 1.0 / (float)$iMaxFramesPerSecond;
        $this->fFirst              = microtime(true);
    }

    public function __destruct() {
        if ($this->iFrameNumber) {
            $fAverageSleepPerFrame = $this->fTotalSlept / (float)$this->iFrameNumber;

            printf(
                "\nPerf: %d frames @ %d fps, %.2f ms/frame, %.2f ms asleep. Free: %.02f%%\n",
                $this->iFrameNumber,
                $this->iMaxFramesPerSecond,
                1000.0 * $this->fFrameDuration,
                1000.0 * $fAverageSleepPerFrame,
                100.0 * $fAverageSleepPerFrame / $this->fFrameDuration
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getMaxFramesPerSecond(): int {
        return $this->iMaxFramesPerSecond;
    }

    /**
     * @inheritDoc
     */
    public function limit(): float {
        ++$this->iFrameNumber;
        $fWakeAt = $this->fFirst + ($this->iFrameNumber * $this->fFrameDuration);
        $fSleepBegins = microtime(true);
        @time_sleep_until($fWakeAt);
        $fSleepEnds = microtime(true);
        $this->fTotalSlept += $fSleepEnds - $fSleepBegins;
        return $fSleepEnds - $this->fFirst;
    }

}

