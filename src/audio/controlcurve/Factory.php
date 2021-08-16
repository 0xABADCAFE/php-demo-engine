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

/**
 * Factory
 *
 * Builds IControlCurve implementations from raw definition data.
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'curve';

    const PRODUCT_TYPES = [
        'Flat'   => 'createFlat',
        'Linear' => 'createRanged',
        'Gamma'  => 'createRanged',
        'Octave' => 'createOctave'
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
        throw new \RuntimeException('Unknown curve type ' . $sType);
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
     * implementation instead of gamma wherever the gamma value is unset ot close to one. If the output
     * range is insignificant over the input range, returns a Flat implementation.
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IControlCurve
     */
    private function createRanged(object $oDefinition, string $sType) : Audio\IControlCurve {
        $fMinOutput = (float)($oDefinition->fMinOutput ?? 0.0);
        $fMaxOutput = (float)($oDefinition->fMaxOutput ?? 1.0);

        // For tiny ranges output ranges, just create a flat output.
        if (\abs($fMaxOutput - $fMinOutput) < 1e-4) {
            return new Flat(0.5*($fMinOutput + $fMaxOutput));
        }

        $fMinInput  = (float)($oDefinition->fMinInput ?? Audio\IControlCurve::DEF_RANGE_MIN);
        $fMaxInput  = (float)($oDefinition->fMaxInput ?? Audio\IControlCurve::DEF_RANGE_MAX);
        $fGamma     = (float)($oDefinition->fGamma ?? 1.0);

        // Don't create a flat gamma curve.
        if ('Gamma' === $sType && \abs($fGamma - 1.0) < 1e-4) {
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

    /**
     * Create an Octave curve.
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IControlCurve
     */
    private function createOctave(object $oDefinition, string $sType) : Audio\IControlCurve {
        $fCentreOutput   = (float)($oDefinition->fCentreOutput ?? 1.0);
        $fScalePerOctave = (float)($oDefinition->fScalePerOctave ?? 1.0);
        $fStepsPerOctave = (float)($oDefinition->fStepsPerOctave ?? Audio\Note::SEMIS_PER_OCTAVE);
        $fCentrePosition = (float)($oDefinition->fCentrePosition ?? Audio\Note::CENTRE_REFERENCE);
        return new Octave($fCentreOutput, $fScalePerOctave, $fCentrePosition, $fStepsPerOctave);
    }
}
