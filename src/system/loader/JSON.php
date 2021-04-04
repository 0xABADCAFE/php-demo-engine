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

namespace ABadCafe\PDE\System\Loader;
use ABadCafe\PDE\System;
use ABadCafe\PDE\System\Definition;

/**
 * JSON implementation for ILoader
 */
class JSON implements System\ILoader {

    private Definition\Display $oDisplay;

    /**
     * @var Definition\Routine[] $aRoutines
     */
    private array $aRoutines;

    /**
     * @var Definition\Event[] $aTimeline
     */
    private array $aTimeline;

    /**
     * @inheritDoc
     */
    public function __construct(string $sFilePath) {
        if (!file_exists($sFilePath) || !is_readable($sFilePath)) {
            throw new \Exception('Unable to open ' . $sFilePath . ' for reading');
        }
        $oDocument = json_decode(file_get_contents($sFilePath));
        if (!$oDocument) {
            throw new \Exception('Unable to parse ' . $sFilePath . ', invalid JSON?');
        }

        if (!isset($oDocument->display) || !is_object($oDocument->display)) {
            throw new \Exception('Missing or invalid display section');
        }

        $this->oDisplay = new Definition\Display($oDocument->display);

        if (!isset($oDocument->routines) || !is_object($oDocument->routines)) {
            throw new \Exception('Missing or invalid routines section');
        }

        foreach ($oDocument->routines as $sName => $oJSON) {
            $this->aRoutines[$sName] = new Definition\Routine($oJSON);
        }

        if (!isset($oDocument->timeline) || !is_array($oDocument->timeline)) {
            throw new \Exception('Missing or invalid timeline section');
        }

        foreach ($oDocument->timeline as $oJSON) {
            $this->aTimeline[] = new Definition\Event($oJSON);
        }
    }

    /**
     * @inheritDoc
     */
    public function getDisplayDefinition() : Definition\Display {
        return $this->oDisplay;
    }

    /**
     * @inheritDoc
     */
    public function getRoutines() : array {
        return $this->aRoutines;
    }

    /**
     * @inheritDoc
     */
    public function getTimeline() : array {
        return $this->aTimeline;
    }
}

