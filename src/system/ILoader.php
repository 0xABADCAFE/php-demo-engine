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

/**
 * ILoader
 *
 * Interface for demo file loaders.
 */
interface ILoader {

    /**
     * Expect to load from a file specified as a string.
     *
     * @param string $sFilePath
     */
    public function __construct(string $sFilePath);

    /**
     * Return an associative array of the Display definitions in file.
     *
     * @return Definition\Display[] - keyed by identifer
     */
    public function getDisplays() : array;

    /**
    * Return an associative array of the Routine definitions in the file.
     *
     * @return Definition\Routine[] - keyed by identifier
     */
    public function getRoutines() : array;

    /**
     * @return Definition\Event[]
     */
    public function getEvents() : array;
}
