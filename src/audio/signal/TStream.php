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
 * TStream
 */
trait TStream {

    private static ?Packet $oSilence;

    protected bool $bEnabled = true;

    protected static function initStreamTrait() {
        self::$oSilence = Packet::create();
    }

    protected function emitSilence() : Packet {
        return self::$oSilence;
    }

    /**
     * Enable a stream.
     */
    public function enable() : IStream {
        $this->bEnabled = true;
        return $this;
    }

    /**
     * Disable a stream. A disabled stream will emit silence packets if invoked.
     */
    public function disable() : IStream {
        $this->bEnabled = false;
        return $this;
    }

    /**
     * Check if a stream is enabled.
     */
    public function isEnabled() : bool {
        return $this->bEnabled;
    }

}
