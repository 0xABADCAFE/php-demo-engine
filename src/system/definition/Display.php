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
 * Display
 *
 * Definition structure for a Display
 */
class Display {

    use TDefinition;

    const DEFAULTS = [
        'sType'   => 'PlainASCII',
        'iWidth'  => 160,
        'iHeight' =>  50,
        'iMaxFPS' =>  30
    ];

    public string $sType;
    public int    $iWidth, $iHeight, $iMaxFPS;

    /**
     * Constructor
     *
     * @param \stdClass $oRaw
     */
    public function __construct(\stdClass $oRaw) {
        $this->mapFromRaw($oRaw);
    }
}
