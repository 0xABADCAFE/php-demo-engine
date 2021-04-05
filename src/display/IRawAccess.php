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

namespace ABadCafe\PDE\Display;

/**
 * IRawAccess
 *
 * Interface for displays that support raw character buffer access.
 *
 * TODO split this out here but it needs tidying.
 */
interface IRawAccess {

    /**
     * Get the raw display buffer, aka 1337 mode, lol
     *
     * Modifications made here can be rendered by a call to redraw().
     * Calling refresh() is likely to destroy.
     *
     * @return string&
     */
    public function &getRaw() : string;

    /**
     * @return string
     */
    public function getRawLuma() : string;

    /**
     * @return int
     */
    public function getMaxRawLuma() : int;

}
