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

namespace ABadCafe\PDE\Util;

/**
 * TSometimesShareable
 *
 * Mixin for ISometimesSharable implementors that want per instance control over sharing.
 */
trait TSometimesShareable {

    protected bool $bSharable = true;

    /**
     * @return self
     */
    public function enableSharing(): self {
        $this->bSharable = true;
        return $this;
    }

    /**
     * @return self
     */
    public function disableSharing(): self {
        $this->bSharable = false;
        return $this;
    }

    /**
     * @return self
     */
    public function share(): self {
        return $this->bSharable? $this : clone $this;
    }
}
