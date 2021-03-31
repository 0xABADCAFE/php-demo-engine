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

namespace ABadCafe\PDE\Routine;

use ABadCafe\PDE;

/**
 * TRoutine
 *
 * Mixin trait to provide default expected constructor and parameter manipulation. The composing class is expected to
 * implement a constant array DEFAULT_PARAMETERS that forms a key/value set of the controllable parameter names and
 * their default value (and expected type).
 *
 */
trait TRoutine {

    /**
     * @var object $oParameters - basic key value structure
     */
    private object $oParameters;

    /**
     * @var PDE\IDisplay $oDisplay
     */
    private PDE\IDisplay $oDisplay;

    /**
     * Basic constructor
     *
     * @implements IRoutine::__construct()
     */
    public function __construct(PDE\IDisplay $oDisplay, array $aParameters = []) {
        $this->oParameters = (object)self::DEFAULT_PARAMETERS;
        $this->setDisplay($oDisplay);
        $this->setParameters($aParameters);
    }

    /**
     * @inheritDoc
     * @implements IRoutine::setParameters()
     *
     * Each input value is key checked against the DEFAULT_PARAMETERS set and if the key matches the
     * value is first type cooerced then assigned.
     */
    public function setParameters(array $aParameters) : self {
        foreach ($aParameters as $sParameterName => $mParameterValue) {
            if (isset(self::DEFAULT_PARAMETERS[$sParameterName])) {
                settype($mParameterValue, gettype(self::DEFAULT_PARAMETERS[$sParameterName]));
                $this->oParameters->{$sParameterName} = $mParameterValue;
            }
        }
        return $this;
    }
}
