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
        'NoOp'              => NoOp::class,
        '2D/ASCIILines'     => SimpleLine::class,
        '2D/StaticNoise'    => StaticNoise::class,
        '2D/RGBPulse'       => RGBPulse::class,
        '2D/RGBPersistence' => RGBPersistence::class,
        '2D/RGBImage'       => RGBImage::class,
        '2D/RGBMask'        => RGBMask::class,
        '2D/RGBFire'        => RGBFire::class,
        '2D/TapeLoader'     => TapeLoader::class,
        '3D/Toroid'         => Toroid::class,
        '3D/Voxel'          => Voxel::class,
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
        // Issue #8 - Can't dereference array and call in single step until PHP8
        $sClassName = self::TYPES[$sKind];
        return new $sClassName($oDisplay, $aParameters);
    }
}

