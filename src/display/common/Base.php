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

/**
 * Base
 *
 * Common base class for displays
 */
abstract class Base implements PDE\IDisplay {

    protected int $iWidth, $iHeight;

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        if ($iWidth < self::I_MIN_WIDTH || $iHeight < self::I_MIN_HEIGHT) {
            throw new \RangeException('Invalid dimensions');
        }
        $this->iWidth  = $iWidth;
        $this->iHeight = $iHeight;
    }

    /**
     * Make sure we restore the cursor
     */
    public function __destruct() {
        echo IANSIControl::CRSR_ON;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        printf(IANSIControl::TERM_SIZE_TPL, $this->iHeight + 2, $this->iWidth + 1);
        $this->clear();
        echo IANSIControl::TERM_CLEAR . IANSIControl::CRSR_OFF;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWidth(): int {
        return $this->iWidth;
    }

    /**
     * @inheritDoc
     */
    public function getHeight(): int {
        return $this->iHeight;
    }

    /**
     * @inheritDoc
     */
    public function waitForFrame(): PDE\IDisplay {
        return $this;
    }

    /**
     * Each input value is key checked against the DEFAULT_PARAMETERS set and if the key matches the
     * value is first type cooerced then assigned.
     *
     * @param  mixed[] $aParameters
     * @return \stdClass
     */
    protected function filterRawParameters(array $aParameters): \stdClass {
        $aDefaults = static::DEFAULT_PARAMETERS;
        $aFiltered = [];
        foreach ($aParameters as $sParameterName => $mParameterValue) {
            if (isset($aDefaults[$sParameterName])) {
                settype($mParameterValue, gettype($aDefaults[$sParameterName]));
                $aFiltered[$sParameterName] = $mParameterValue;
            }
        }
        return (object)$aFiltered;
    }
}
