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
use function ABadCafe\PDE\dprintf;

/**
 * None
 *
 * Used for benchmarking everything else
 */
class None implements Audio\IPCMOutput {

    /**
     * Open the output stream. Throws an exception if it is not possible to open aplay for output.
     *
     * @throws \Exception
     */
    public function open() {

    }

    /**
     * Write a signal packet. This involves scaling, quantising values and limiting them before writing.
     *
     * @param Signal\Packet $oPacket
     */
    public function write(Audio\Signal\Packet $oPacket) {

    }

    /**
     * Close down the output handle and subprocess.
     */
    public function close() {

    }
}
