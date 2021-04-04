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

/**
 * Simple rate limiter.
 */
class Simple implements System\IRateLimiter {

    private int $iMaxFramesPerSecond, $iFrameNumber = 0;

    private float $fFirst, $fInverseRate;

    public function __construct(int $iMaxFramesPerSecond) {
        if ($iMaxFramesPerSecond < self::MIN_FPS_LIMIT || $iMaxFramesPerSecond > self::MAX_FPS_LIMIT) {
            throw new \RangeException();
        }
        $this->iMaxFramesPerSecond = $iMaxFramesPerSecond;
        $this->fInverseRate        = 1.0 / (float)$iMaxFramesPerSecond;
        $this->fFirst              = microtime(true);
    }

    /**
     * @return int
     */
    public function getMaxFramesPerSecond() : int {
        return $this->iMaxFramesPerSecond;
    }

    /**
     * Inject a delay.
     *
     * @return float - time since created (in seconds)
     */
    public function limit() : float {
        ++$this->iFrameNumber;
        $fWakeAt = $this->fFirst + ($this->iFrameNumber * $this->fInverseRate);
        @time_sleep_until($fWakeAt);
        return microtime(true) - $this->fFirst;
    }

}

