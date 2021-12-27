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

namespace ABadCafe\PDE\Graphics;

/**
 * ImageCache
 */
class ImageCache {

    /**
     * @var array<string, Image> $aCache
     */
    private static $aCache = [];

    public static function reset(): void {
        self::$aCache = [];
    }

    public static function has(string $sHandle): bool {
        return isset(self::$aCache[$sHandle]);
    }

    public static function get(string $sHandle): Image {
        if (!isset(self::$aCache[$sHandle])) {
            throw new \OutOfBoundsException();
        }
        return self::$aCache[$sHandle];
    }

    public static function set(string $sHandle, Image $oImage): void {
        self::$aCache[$sHandle] = $oImage;
    }
}
