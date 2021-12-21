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

namespace ABadCafe\PDE\Audio;

/**
 * IPCMOutput
 *
 */
interface IPCMOutput {

    const
        SCALE       = 32767.0,
        MIN_LEVEL   = -32767,
        MAX_LEVEL   = 32767
    ;

    /**
     * Open the output stream. Throws an exception if it is not possible to open aplay for output.
     *
     * @throws \Exception
     */
    public function open(): void;

    /**
     * Write a signal packet. This involves scaling, quantising values and limiting them before writing.
     *
     * @param Signal\Packet<float> $oPacket
     */
    public function write(Signal\Packet $oPacket): void;

    /**
     * Close down the output handle and subprocess.
     */
    public function close(): void;
}
