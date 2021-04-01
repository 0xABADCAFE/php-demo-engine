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

namespace ABadCafe\PDE\System;

/**
 * RateLimiter
 */
class RateLimiter {

    const
        MIN_FPS_LIMIT = 5,
        MAX_FPS_LIMIT = 120
    ;

    private int $iMaxFramesPerSecond, $iNumFramesSkipped;

    private float $fMinPeriod, $fFirst, $fPrevious;

    public function __construct(int $iMaxFramesPerSecond) {
        if ($iMaxFramesPerSecond < self::MIN_FPS_LIMIT || $iMaxFramesPerSecond > self::MAX_FPS_LIMIT) {
            throw new \RangeException();
        }
        $this->iNumFramesSkipped   = 0;
        $this->iMaxFramesPerSecond = $iMaxFramesPerSecond;
        $this->fMinPeriod          = 1.0 / $iMaxFramesPerSecond;
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
     * @return int
     */
    public function getNumFramesSkipped() : int {
        return $this->iNumFramesSkipped;
    }

    /**
     * Inject a delay that hopefully matches the desired cap.
     *
     * @return float - time since created (in seconds)
     */
    public function limit() : float {
        $fCurrent        = microtime(true);
        $fElapsed        = $fCurrent - $this->fPrevious;
        $this->fPrevious = $fCurrent;
        if ($fElapsed < $this->fMinPeriod) {
            $iDelayTime = (int)(1e6 * ($this->fMinPeriod - $fElapsed));
            if ($iDelayTime > 100) {
                usleep($iDelayTime - 50);
            }
        } else {
            ++$this->iNumFramesSkipped;
        }
        return $fCurrent - $this->fFirst;
    }
}

