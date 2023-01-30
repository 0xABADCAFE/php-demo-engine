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

//error_reporting(-1);

/**
 * If you aren't using 7.4.15 no dice. Lower versions have buggy covariance.
 */
if (PHP_VERSION_ID < 70415) {
    throw new \RuntimeException("Requires at least PHP 7.4.15");
}

/**
 * Basic classmap autoloader
 */
require_once 'classmap.php';
\spl_autoload_register(function(string $str_class) {
    if (isset(CLASS_MAP[$str_class])) {
        require_once __DIR__ . CLASS_MAP[$str_class];
    }
});

/**
 * Debugging output
 *
 * @param float|int|string $mVarArgs
 */
function dprintf(string $sTemplate, ...$mVarArgs): void {
    \fprintf(STDERR, $sTemplate, ...$mVarArgs);
}
