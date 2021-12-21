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
 * TDefinition
 *
 * Common trait for mapping raw object data to Definition classes. Class is expected to provide a DEFAULTS array
 * constant that defines the keys and their default values.
 */
trait TDefinition {

    /**
     * Maps a raw object to entity field structure.
     *
     * @param \stdClass $oRaw
     */
    protected function mapFromRaw(\stdClass $oRaw): void {
        foreach ($oRaw as $sField => $mValue) {
            if (isset(self::DEFAULTS[$sField])) {
                settype($mValue, gettype(self::DEFAULTS[$sField]));
                $this->{$sField} = $mValue;
            } else {
                $this->{$sField} = self::DEFAULTS[$sField];
            }
        }
    }
}

