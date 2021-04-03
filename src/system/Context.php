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

use ABadCafe\PDE;

/**
 * Context
 */

class Context {

    private PDE\IDisplay $oDisplay;

    private IRateLimiter $oRateLimiter;

    /**
     * @var PDE\IRoutine[] $aRoutineInstances
     */
    private array $aRoutineInstances = [];

    /**
     * @var int[] $aRoutinePriorities
     */
    private array $aRoutinePriorities = [];

    /**
     * @var bool[] $aRoutinePriorities
     */
    private array $aRoutineStatuses = [];

    /**
     * Constructor
     *
     * @param PDE\IDisplay $oDisplay
     * @param IRateLimiter $oLimiter
     */
    public function __construct(PDE\IDisplay $oDisplay, IRateLimiter $oRateLimiter) {
        $this->oDisplay     = $oDisplay;
        $this->oRateLimiter = $oRateLimiter;
    }

    /**
     * Registers a Routine. Each routine should have a unique name and priority.
     *
     * @param  PDE\IRoutine $oRoutine
     * @param  string       $sIdentity
     * @param  int          $iPriority
     * @param  bool         $bEnabled
     * @return self
     * @throws \InvalidArgumentException
     */
    public function registerRoutine(PDE\IRoutine $oRoutine, string $sIdentity, int $iPriority, bool $bEnabled = false) : self {
        if (empty($sIdentity) || isset($this->aRoutineInstances[$sIdentity])) {
            throw new \InvalidArgumentException();
        }
        $this->aRoutineInstances[$sIdentity]  = $oRoutine;
        $this->aRoutinePriorities[$sIdentity] = $iPriority;
        $this->aRoutineStatuses[$sIdentity]   = $bEnabled;
        return $this;
    }

    /**
     * This is just a prototype method that will be broken out.
     */
    public function runUntil(float $fUntil) {
        asort($this->aRoutinePriorities, SORT_NUMERIC);
        $iFrameNumber = 0;
        $fTimeIndex   = 0.0;
        while ($fTimeIndex < $fUntil) {
            $this->oDisplay->clear();
            foreach ($this->aRoutinePriorities as $sIdentity => $iPrority) {
                $this->aRoutineInstances[$sIdentity]->render($iFrameNumber, $fTimeIndex);
            }
            $this->oDisplay->redraw();
            $fTimeIndex = $this->oRateLimiter->limit();
            $iFrameNumber++;
            printf("t:%0.4fs f:%d %0.1f fps", $fTimeIndex, $iFrameNumber, $iFrameNumber/$fTimeIndex);
        }
        echo "\n";

    }
}
