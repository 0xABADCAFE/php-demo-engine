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
 * IAsynchronous
 *
 * Messaging properties for Asynchronous Processes
 */
interface IAsynchronous {

    const
        ID_PARENT = 1,
        ID_CHILD  = 0,

        MAX_RETRIES = 3,
        RETRY_PAUSE = 100,

        /**
         * Header structure
         *
         * uint32[4] { magic, command, size, magic^command^size }
         */
        HEADER_SIZE            = 16,
        HEADER_OFFSET_MAGIC    = 0,
        HEADER_OFFSET_COMMAND  = 1,
        HEADER_OFFSET_SIZE     = 2,
        HEADER_OFFSET_CHECK    = 3,
        HEADER_MAGIC           = 0xABADCAFE
    ;

}
