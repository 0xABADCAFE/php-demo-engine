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

    /**
     * @var Definition\Display[] $aDisplays
     */
    private array $aDisplays;

    /**
     * @var Definition\Routine[] $aRoutines
     */
    private array $aRoutines;

    /**
     * @var Definition\Event[] $aEvents
     */
    private array $aEvents;

    private string $sBasePath;

    /**
     * @inheritDoc
     */
    public function __construct(string $sFilePath) {
        if (!\file_exists($sFilePath) || !\is_readable($sFilePath)) {
            throw new \Exception('Unable to open ' . $sFilePath . ' for reading');
        }
        $oDocument = \json_decode(\file_get_contents($sFilePath));
        if (!$oDocument) {
            throw new \Exception('Unable to parse ' . $sFilePath . ', invalid JSON?');
        }

        $this->sBasePath = \dirname($sFilePath) . '/';

        if (
            !isset($oDocument->displays) ||
            !\is_object($oDocument->displays) ||
            empty($oDocument->displays)
        ) {
            throw new \Exception('Missing or invalid display section');
        }

        foreach ($oDocument->displays as $sName => $oJSON) {
            $this->aDisplays[$sName] = new Definition\Display($oJSON);
        }

        if (
            !isset($oDocument->routines) ||
            !\is_object($oDocument->routines) ||
            empty($oDocument->routines)
        ) {
            throw new \Exception('Missing or invalid routines section');
        }

        foreach ($oDocument->routines as $sName => $oJSON) {
            $this->aRoutines[$sName] = new Definition\Routine($oJSON);
        }

        if (!isset($oDocument->events) || !\is_array($oDocument->events)) {
            throw new \Exception('Missing or invalid timeline section');
        }

        foreach ($oDocument->events as $oJSON) {
            $this->aEvents[] = new Definition\Event($oJSON);
        }
    }

    /**
     * @inheritDoc
     */
    public function getBasePath() : string {
        return $this->sBasePath;
    }

    /**
     * @inheritDoc
     */
    public function getDisplays() : array {
        return $this->aDisplays;
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
    public function getEvents() : array {
        return $this->aEvents;
    }
}

