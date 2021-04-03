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
 * Adaptive ratelimiter
 *
 * On systems where the Simple rate limiter doesn't work properly for any reason, this provides a
 * poor man's alternative based on continually adjustint the delay based on measuring the FPS rate.
 */
class Adaptive implements System\IRateLimiter {

    private int $iMaxFramesPerSecond;
    private int $iFrameNumber = 0;

    private float $fFirst, $fTargetDelay, $fAdjustedDelay;

    public function __construct(int $iMaxFramesPerSecond) {
        if ($iMaxFramesPerSecond < self::MIN_FPS_LIMIT || $iMaxFramesPerSecond > self::MAX_FPS_LIMIT) {
            throw new \RangeException();
        }
        $this->iMaxFramesPerSecond = $iMaxFramesPerSecond;
        $this->fAdjustedDelay      = // fall through
        $this->fTargetDelay        = (1000000.0 / $iMaxFramesPerSecond);
        $this->fFirst              = // fall through
        $this->fPrevious           = microtime(true);
    }

    /**
     * @return int
     */
    public function getMaxFramesPerSecond() : int {
        return $this->iMaxFramesPerSecond;
    }

    /**
     * Inject a delay. We start off injecting the initial target value.
     *
     * @return float - time since created (in seconds)
     */
    public function limit() : float {
        ++$this->iFrameNumber;
        usleep((int)($this->fAdjustedDelay));
        $fTimeIndex            = microtime(true) - $this->fFirst;
        $fFramesPerSecond      = $this->iFrameNumber / $fTimeIndex;

        $fAdjustment = $fFramesPerSecond / $this->iMaxFramesPerSecond;

        $this->fAdjustedDelay = $this->fTargetDelay * pow($fAdjustment, 5);
        return $fTimeIndex;
    }

}

