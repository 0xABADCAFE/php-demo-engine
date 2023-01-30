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

namespace ABadCafe\PDE\Audio\Output;
use ABadCafe\PDE\Audio;

use function \sprintf;

/**
 * Sox
 *
 * Simple pipe wrapper for sox play
 */
class Sox extends Piped {

    /**
     * @inheritDoc
     */
    protected function createOutputCommand(): string {
        return sprintf(
            'play -t raw -b 16 -c 1 -e signed --endian=little -r %d --buffer %d - -c 2 reverb', // ....?
            Audio\IConfig::PROCESS_RATE,
            self::BUFFER_SIZE * 2
        );
    }
}
