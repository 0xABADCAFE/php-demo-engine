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

namespace ABadCafe\PDE\System\Loader\Defintion;

/**
 * Event
 */
class Event {

    /**
     * Integer enumeration of event type
     */
    const
        END     = 0,
        ENABLE  = 1,
        DISABLE = 2,
        UPDATE  = 3
    ;

    /**
     * Map of JSON file "do" to event type
     */
    const DO_ACTIONS = [
        'end'     => self::END,
        'enable'  => self::ENABLE,
        'disable' => self::DISABLE,
        'update'  => self::UPDATE
    ];

    public float  $fAtTimeIndex;
    public int    $iAction;
    public string $sTarget = '';
    public array  $aParameters = [];

    /**
     * Constructor
     */
    public function __construct(object $oJSON) {
        if (!isset($oJSON->at) || !isset($oJSON->do) || !isset(self::DO_ACTIONS[(string)$oJSON->do])) {
            throw new \Exception("Missing expected/valid 'at' or 'do' directive");
        }
        $this->fAtTimeIndex = (float)$oJSON->at;
        $this->iAction      = self::DO_ACTIONS[(string)$oJSON->do];
        if (isset($oJSON->aParameters)) {
            $this->aParameters = (array)$oJSON->aParameters;
        }
    }

}
