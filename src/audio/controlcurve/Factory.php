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

namespace ABadCafe\PDE\Audio\ControlCurve;

use ABadCafe\PDE\Audio;

class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'curve';

    const PRODUCT_TYPES = [
        'Flat'   => 'createFlat',
        'Linear' => 'createRanged',
        'Gamma'  => 'createRanged'
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\IControlCurve {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown envelope type ' . $sType);
    }

    /**
     * Create the invariant flat curve.
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IControlCurve
     */
    private function createFlat(object $oDefinition, string $sType) : Audio\IControlCurve {
        $fValue = (float)($oDefinition->fFixed ?? 0.5);
        return new Flat($fValue);
    }

    /**
     * Create either the linear or gamma curve, depending on the type and gamma values. Returns a linear
     * implementation instead of gamma wherever the gamma value is unset ot close to one.
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IControlCurve
     */
    private function createRanged(object $oDefinition, string $sType) : Audio\IControlCurve {
        $fMinInput  = (float)($oDefinition->fMinInput ?? Audio\IControlCurve::DEF_RANGE_MIN);
        $fMaxInput  = (float)($oDefinition->fMaxInput ?? Audio\IControlCurve::DEF_RANGE_MAX);
        $fMinOutput = (float)($oDefinition->fMinOutput ?? 0.0);
        $fMaxOutput = (float)($oDefinition->fMaxOutput ?? 1.0);
        $fGamma     = (float)($oDefinition->fGamma ?? 1.0);

        // Don't create a flat gamma curve.
        if ('gamma' === $sType && abs($fGamma - 1.0) < 0.0001) {
            $sType = 'Linear';
        }

        switch ($sType) {
            case 'Linear':
                return new Linear($fMinOutput, $fMaxOutput, $fMinInput, $fMaxInput);
            case 'Gamma':
                return new Gamma($fMinOutput, $fMaxOutput, $fGamma, $fMinInput, $fMaxInput);
        }
        throw new \RuntimeException('Unknown control curve type ' . $sType);
    }

}
