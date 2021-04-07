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

namespace ABadCafe\PDE\Routine;

use ABadCafe\PDE;

/**
 * Factory
 *
 * Basic singleton for constructing a PDE\IDisplay implementation for a given desrciption.
 */
class Factory {

    const TYPES = [
        'NoOp'       => NoOp::class,
        'SimpleLine' => SimpleLine::class,
        'Toroid'     => Toroid::class,
        'RGBPulse'   => RGBPulse::class
    ];

    private static ?self $oInstance = null;

    /**
     * Singleton constructor
     */
    private function __construct() {

    }

    /**
     * Singleton accessor
     *
     * @return self
     */
    public static function get() : self {
        if (null === self::$oInstance) {
            self::$oInstance = new self;
        }
        return self::$oInstance;
    }

    /**
     * Factory method
     *
     * @param  string $sKind
     * @paran  int    $iWidth
     * @param  int    $iHeight
     * @return PDE\IDisplay
     */
    public function create(string $sKind, PDE\IDisplay $oDisplay, array $aParameters = []) : PDE\IRoutine {
        if (!isset(self::TYPES[$sKind])) {
            throw new \OutOfBoundsException($sKind . ' is not a known IRoutine type)');
        }
        return new (self::TYPES[$sKind])($oDisplay, $aParameters);
    }
}

