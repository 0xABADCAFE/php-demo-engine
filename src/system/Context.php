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
 *
 * This class coordinates the entire show. It is created with an ILoader implementation from which it gets the
 * overall definition of the demo and internally constructs and parameterises all the runtime components from
 * it.
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
     * @var Definition\Event[][] $aEventsByFrameIndex
     */
    private array $aEventsByFrameIndex = [];

    /**
     * Constructor
     *
     * @param ILoader $oLoader
     */
    public function __construct(ILoader $oLoader) {
        $this->initialiseDisplay($oLoader->getDisplayDefinition());
        $this->initialiseRoutines($oLoader->getRoutines());
        $this->initialiseTimeline($oLoader->getTimeline());
    }

    /**
     * Initialie the display properties
     *
     * @param Definition\Display $oDisplayDefinition
     */
    private function initialiseDisplay(Definition\Display $oDisplayDefinition) {
        $this->oDisplay     = PDE\Display\Factory::get()->create(
            $oDisplayDefinition->sType,
            $oDisplayDefinition->iWidth,
            $oDisplayDefinition->iHeight
        );
        $this->oRateLimiter = new RateLimiter\Simple($oDisplayDefinition->iMaxFPS);
    }

    /**
     * Initialie the Routine list
     *
     * @param Definition\Routine[] $aRoutineDefinitions
     */
    private function initialiseRoutines(array $aRoutineDefinitions) {
        $oRoutineFactory = PDE\Routine\Factory::get();
        foreach ($aRoutineDefinitions as $sIdentity => $oRoutineDefinition) {

            if (isset($this->aRoutineInstances[$sIdentity])) {
                throw new \Exception('Duplicate routine identity ' . $sIdentity);
            }
            $this->aRoutineInstances[$sIdentity] = $oRoutineFactory->create(
                $oRoutineDefinition->sType,
                $this->oDisplay,
                $oRoutineDefinition->aParameters
            );
            $this->aRoutinePriorities[$sIdentity] = $oRoutineDefinition->iPriority;
        }
        asort($this->aRoutinePriorities, SORT_NUMERIC);
    }

    /**
     * Initialie the Event Timeline
     *
     * @param Definition\Event[] $aEventDefinitions
     */
    private function initialiseTimeline(array $aEventDefinitions) {
        $iFramesPerSecond = $this->oRateLimiter->getMaxFramesPerSecond();
        foreach ($aEventDefinitions as $oEvent) {
            $iFrameIndex = (int)($oEvent->fAtTimeIndex * $iFramesPerSecond);
            if (isset($this->aEventsByFrameIndex[$iFrameIndex])) {
                $this->aEventsByFrameIndex[$iFrameIndex][] = $oEvent;
            } else {
                $this->aEventsByFrameIndex[$iFrameIndex] = [$oEvent];
            }
        }
    }

    public function run() {
        $iFrameNumber   = 0;
        $fTimeIndex     = 0.0;
        while ($this->handleEvents($iFrameNumber, $fTimeIndex)) {
            $this->oDisplay->clear();
            $this->runRoutines($iFrameNumber, $fTimeIndex);
            $this->oDisplay->redraw();
            $fTimeIndex = $this->oRateLimiter->limit();
            $iFrameNumber++;
            printf("t:%0.4fs f:%d %0.1f fps", $fTimeIndex, $iFrameNumber, $iFrameNumber/$fTimeIndex);
        }
        echo "\n";
    }

    /**
     * Deal with any events on the frame index
     *
     * @param int $iFrameNumber
     */
    private function handleEvents(int $iFrameNumber, float $fTimeIndex) {
        if (!empty($this->aEventsByFrameIndex[$iFrameNumber])) {
            foreach ($this->aEventsByFrameIndex[$iFrameNumber] as $oEvent) {
                if (Definition\Event::END == $oEvent->iAction) {
                    return false;
                } else if (isset($this->aRoutineInstances[$oEvent->sTarget])) {
                    $oRoutine = $this->aRoutineInstances[$oEvent->sTarget];
                    switch ($oEvent->iAction) {
                        case Definition\Event::ENABLE:
                            $oRoutine->enable($iFrameNumber, $fTimeIndex);
                            break;
                        case Definition\Event::DISABLE:
                            $oRoutine->disable($iFrameNumber, $fTimeIndex);
                            break;
                        case Definition\Event::UPDATE:
                            $oRoutine->setParameters($oEvent->aParameters);
                            break;
                        default:
                            break;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Run the set of currently active routines, in priority order.
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function runRoutines(int $iFrameNumber, float $fTimeIndex) {
        foreach ($this->aRoutinePriorities as $sIdentity => $iPrority) {
            $this->aRoutineInstances[$sIdentity]->render($iFrameNumber, $fTimeIndex);
        }
    }


}
