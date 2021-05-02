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

    const
        NS_DISPLAY = 'display/',
        NS_ROUTINE = 'routine/',
        DEFAULT_DISPLAY = self::NS_DISPLAY . 'default'
    ;

    /**
     * The active display
     */
    private PDE\IDisplay $oDisplay;

    private IRateLimiter $oRateLimiter;

    /**
     * @var PDE\IDisplay[] $aRoutineInstances
     */
    private array $aDisplayInstances = [];

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
        $this->initialiseDisplays($oLoader->getDisplays());
        $this->initialiseRoutines($oLoader->getRoutines(), $oLoader->getBasePath());
        $this->initialiseTimeline($oLoader->getEvents());
    }

    /**
     * Run the thing.
     */
    public function run() {
        $iFrameNumber   = 0;
        $fTimeIndex     = 0.0;
        while ($this->handleEvents($iFrameNumber, $fTimeIndex)) {
            $this->oDisplay->clear();
            $this->runRoutines($iFrameNumber, $fTimeIndex);
            $this->oDisplay->redraw();
            $fTimeIndex = $this->oRateLimiter->limit();
            $iFrameNumber++;
        }
        // Wait for the last frame to be complete before exit.
        $this->oDisplay->waitForFrame();
        echo "\n";
    }

    /**
     * Initialie the display properties
     *
     * @param Definition\Display[] $aDisplays
     */
    private function initialiseDisplays(array $aDisplayDefinitions) {
        $oDisplayFactory = PDE\Display\Factory::get();
        foreach ($aDisplayDefinitions as $sIdentity => $oDisplayDefinition) {
            $sIdentity = self::NS_DISPLAY . $sIdentity;
            if (isset($this->aDisplayInstances[$sIdentity])) {
                throw new \Exception('Duplicate display identity ' . $sIdentity);
            }
            $this->aDisplayInstances[$sIdentity] = $oDisplayFactory->create(
                $oDisplayDefinition->sType,
                $oDisplayDefinition->iWidth,
                $oDisplayDefinition->iHeight
            );
        }

        $this->oDisplay     = $this->aDisplayInstances[self::DEFAULT_DISPLAY] ?? reset($this->aDisplayInstances);
        $oDisplayDefinition = $aDisplayDefinitions[self::DEFAULT_DISPLAY]     ?? reset($aDisplayDefinitions);
        $this->oRateLimiter = new RateLimiter\Simple($oDisplayDefinition->iMaxFPS);
    }

    /**
     * Initialie the Routine list
     *
     * @param Definition\Routine[] $aRoutineDefinitions
     */
    private function initialiseRoutines(array $aRoutineDefinitions, string $sBasePath) {
        $oRoutineFactory = PDE\Routine\Factory::get();
        foreach ($aRoutineDefinitions as $sIdentity => $oRoutineDefinition) {
            $sIdentity = self::NS_ROUTINE . $sIdentity;
            if (isset($this->aRoutineInstances[$sIdentity])) {
                throw new \Exception('Duplicate routine identity ' . $sIdentity);
            }
            $this->aRoutineInstances[$sIdentity] = $oRoutine = $oRoutineFactory->create(
                $oRoutineDefinition->sType,
                $this->oDisplay,
                $oRoutineDefinition->aParameters
            );
            if ($oRoutine instanceof PDE\Routine\IResourceLoader) {
                $oRoutine
                    ->setBasePath($sBasePath)
                    ->preload();
            }
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

    /**
     * Deal with any events on the frame index
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function handleEvents(int $iFrameNumber, float $fTimeIndex) {
        if (!empty($this->aEventsByFrameIndex[$iFrameNumber])) {
            foreach ($this->aEventsByFrameIndex[$iFrameNumber] as $oEvent) {
                if (Definition\Event::END == $oEvent->iAction) {
                    return false;
                } else if (isset($this->aDisplayInstances[$oEvent->sTarget])) {
                    $this->handleDisplayEvent($oEvent, $iFrameNumber, $fTimeIndex);
                } else if (isset($this->aRoutineInstances[$oEvent->sTarget])) {
                    $this->handleRoutineEvent($oEvent, $iFrameNumber, $fTimeIndex);
                }
            }
        }
        return true;
    }

    /**
     * Handle a display event
     *
     * @param Definition\Event $oEvent
     * @param int              $iFrameNumber
     * @param float            $fTimeIndex
     */
    private function handleDisplayEvent(Definition\Event $oEvent, int $iFrameNumber, float $fTimeIndex) {
        $oDisplay = $this->aDisplayInstances[$oEvent->sTarget];
        switch ($oEvent->iAction) {
            case Definition\Event::ENABLE:
                if ($oDisplay !== $this->oDisplay) {
                    $this->oDisplay->waitForFrame();
                    $this->oDisplay = $oDisplay;
                    foreach ($this->aRoutineInstances as $oRoutine) {
                        $oRoutine->setDisplay($this->oDisplay);
                    }
                }
                break;
            case Definition\Event::UPDATE:
                $this->oDisplay->setParameters($oEvent->aParameters);
                break;
            default:
                break;
        }
    }

    /**
     * Handle a routine event
     *
     * @param Definition\Event $oEvent
     * @param int              $iFrameNumber
     * @param float            $fTimeIndex
     */
    private function handleRoutineEvent(Definition\Event $oEvent, int $iFrameNumber, float $fTimeIndex) {
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
    /**
     * Run the set of currently active routines, in priority order.
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function runRoutines(int $iFrameNumber, float $fTimeIndex) {
        foreach ($this->aRoutinePriorities as $sIdentity => $iPrority) {
            $oRoutine = $this->aRoutineInstances[$sIdentity];
            if ($oRoutine->canRender($iFrameNumber, $fTimeIndex)) {
                $oRoutine->render($iFrameNumber, $fTimeIndex);
            }
        }
    }

}
