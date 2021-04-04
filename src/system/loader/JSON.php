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

namespace ABadCafe\PDE\Loader;

use ABadCafe\PDE;

/**
 * JSON
 */

class JSON {

    public Definition\Display $oDisplay;

    /**
     * @var Definition\Routine[] $aRoutines
     */
    public array $aRoutines;

    /**
     * @var Definition\Event[] $aTimeline
     */
    public array $aTimeline;


    public function __construct(string $sFilePath) {
        if (!file_exists($sFilePath) || !is_readable($sFilePath)) {
            throw new \Exception('Unable to open ' . $sFilePath . ' for reading');
        }
        $oJSON = json_decode(file_get_contents($sFilePath));
        if (!$oJSON) {
            throw new \Exception('Unable to parse ' . $sFilePath . ', invalid JSON?');
        }

        if (!isset($oJSON->display) || !is_object($oJSON->display)) {
            throw new \Exception('Missing or invalid display section');
        }

        $this->oDisplay = new Definition\Display($oJSON->display);

        if (!isset($oJSON->routines) || !is_object($oJSON->routines)) {
            throw new \Exception('Missing or invalid routines section');
        }

        foreach ($oJSON->routines as $sName => $oJSON) {
            $this->aRoutnines[$sName] = new Definition\Routine($oJSON);
        }

        if (!isset($oJSON->timeline) || !is_array($oJSON->timeline)) {
            throw new \Exception('Missing or invalid timeline section');
        }

        foreach ($oJSON->timeline as $oJSON) {
            $this->aTimeline[] = new Definition\Event($oJSON);
        }
    }

}

