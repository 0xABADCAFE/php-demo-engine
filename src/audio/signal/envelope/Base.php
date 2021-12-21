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

namespace ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio;

use function \max;

/**
 * Base class for envelopes
 */
abstract class Base implements Audio\Signal\IEnvelope {

    protected int   $iSamplePosition   = 0;
    protected bool  $bParameterChanged = false;
    protected float $fTimeScale        = 1.0, $fLevelScale = 1.0;

    /**
     * @inheritDoc
     */
    public function setTimeScale(float $fTimeScale) : self {
        $fTimeScale = max($fTimeScale, self::MIN_TIME_SCALE);
        if ($fTimeScale != $this->fTimeScale) {
            $this->fTimeScale = $fTimeScale;
            $this->bParameterChanged = true;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setLevelScale(float $fLevelScale) : self {
        if ($fLevelScale != $this->fLevelScale) {
            $this->fLevelScale = $fLevelScale;
            $this->bParameterChanged = true;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->iSamplePosition   = 0;
        $this->bParameterChanged = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->iSamplePosition;
    }
}
