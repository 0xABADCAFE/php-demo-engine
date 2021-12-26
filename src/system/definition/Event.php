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

namespace ABadCafe\PDE\System\Definition;

/**
 * Event
 *
 * Definition structure for a timeline event.
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
    public string $sTarget = '_';

    /** @var mixed[] $aParameters */
    public array  $aParameters = [];

    public static float $fLastTimeIndex = 0;

    /**
     * Constructor
     *
     * @param \stdClass $oRaw
     */
    public function __construct(\stdClass $oRaw) {
        if (
            (!isset($oRaw->at) && !isset($oRaw->after)) ||
            !isset($oRaw->do) ||
            !isset(self::DO_ACTIONS[(string)$oRaw->do])
        ) {
            throw new \Exception("Missing expected/valid 'at' or 'do' directive");
        }

        // When?
        if (!isset($oRaw->at)) {
            $this->fAtTimeIndex = self::$fLastTimeIndex + (float)$oRaw->after;
        }
        else {
            $this->fAtTimeIndex = (float)$oRaw->at;
        }

        self::$fLastTimeIndex = $this->fAtTimeIndex;

        // What?
        $this->iAction = self::DO_ACTIONS[(string)$oRaw->do];

        // Whom?
        if (isset($oRaw->on)) {
            $this->sTarget = (string)$oRaw->on;
        }

        // With what?
        if (isset($oRaw->aParameters)) {
            $this->aParameters = (array)$oRaw->aParameters;
        }
    }
}
