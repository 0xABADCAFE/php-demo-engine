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
 * IRateLimiter
 *
 * Basic interface ror rate limiters. Rate limiters are intended to cap the maximum FPS so that we can
 * better calculate which frame number a given time index value.
 */
interface IRateLimiter {

    const
        MIN_FPS_LIMIT = 5,
        MAX_FPS_LIMIT = 120
    ;

    /**
     * Constructor
     *
     * @param int $iMaxFramesPerSecond
     */
    public function __construct(int $iMaxFramesPerSecond);

    /**
     * @return int
     */
    public function getMaxFramesPerSecond() : int;

    /**
     * Inject a delay. We start off injecting the initial target value.
     *
     * @return float - time since created (in seconds)
     */
    public function limit() : float;
}
