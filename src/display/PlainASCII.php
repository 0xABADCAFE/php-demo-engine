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
 *                             P(?:ointless|ortable|HP) Demo Engine/
 */

declare(strict_types=1);

namespace ABadCafe\PDE\Display;

/**
 * IDisplay
 */
class PlainASCII implements IDisplay {

    private int $iWidth, $iHeight;


    public function __construct(int $iWidth, int $iHeight) {
        if (
            $iWidth < self::I_MIN_WIDTH ||
            $iHeight < self::I_MIN_HEIGHT
        ) {
            throw new \RangeException('Invalid dimensions');
        }
        $this->iWidth  = $iWidth;
        $this->iHeight = $iHeight;
    }

    public function getWidth()  : int {
        return $this->iWidth;
    }

    public function getHeight() : int {
        return $this->iHeight;
    }

    public function clear()     : self {
        return $this;
    }

    public function refresh()   : self {
        return $this;
    }

    public function getBuffer() {
        return $this;
    }

}
