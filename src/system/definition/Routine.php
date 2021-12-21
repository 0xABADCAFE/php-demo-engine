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
 * Routine
 *
 * Definition stucture for a Routine.
 */
class Routine {

    use TDefinition;

    const DEFAULTS = [
        'sType'       => 'NoOp',
        'iPriority'   => -1,
        'aParameters' =>  []
    ];

    public string $sType;
    public int    $iPriority;

    /**
     * @var mixed[] $aParameters
     */
    public array  $aParameters;

    /**
     * Constructor
     *
     * @param \stdClass $oRaw
     */
    public function __construct(\stdClass $oRaw) {
        $this->mapFromRaw($oRaw);
    }

}
