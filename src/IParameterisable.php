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

namespace ABadCafe\PDE;

/**
 * IParameterisable
 *
 * Interface for entities that support changing of (some of) their parameters during execution.
 */
interface IParameterisable {

    /**
     * Accepts a key/value set of parameters to change. The accepted parameters vary depending on the implementor.
     *
     * The implementation should not throw here. rather:
     *     Unknown parameter names will be ignored.
     *     The type of a parameter will be force cast (where appropriate)
     *     Out of range values will be clamped..
     *
     * @param  mixed[] $aParameters [key => value]
     * @return self  fluent
     */
    public function setParameters(array $aParameters) : self;
}
