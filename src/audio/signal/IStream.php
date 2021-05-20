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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

/**
 * Interface for components that generate a continuous stream of signal data, such as oscillators and envelope
 * generators. Based on the GIMPS implementation, simplified.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
interface IStream {

    /**
     * Get the current stream position
     *
     * @return int
     */
    public function getPosition() : int;

    /**
     * Reset the stream
     *
     * @return IStream
     */
    public function reset() : self;

    /**
     * Emit a Packet. An optional index parameter allows the stream to ascertain if it is being asked repeatedly for
     * the last generated Packet of data and if so, return it. This becomes necessary in complex signal routing where
     * one IStream implementation's output is consumed by multiple inputs.
     *
     * @param  int|null $iIndex
     * @return IPacket
     */
    public function emit(?int $iIndex = null) : Packet;
}
