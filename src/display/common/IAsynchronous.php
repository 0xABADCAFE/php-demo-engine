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
use ABadCafe\PDE;
use \SPLFixedArray;

/**
 * IAsynchronous
 *
 * Messaging properties for Asynchronous Displays
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

        HEADER_MAGIC           = 0xABADCAFE,
        MESSAGE_NEW_FRAME      = 0,
        MESSAGE_SET_WRITEMASK  = 1,

        MESSAGE_WAIT_FOR_FRAME = 2,

        RESPONSE_OK            = 0,

        DATA_FORMAT_8  = 1,
        DATA_FORMAT_32 = 4,
        DATA_FORMAT_64 = 8,

        DATA_FORMAT_MAP = [
            self::DATA_FORMAT_8  => 'C*',
            self::DATA_FORMAT_32 => 'V*',
            self::DATA_FORMAT_64 => 'Q*',
        ]
    ;

}
