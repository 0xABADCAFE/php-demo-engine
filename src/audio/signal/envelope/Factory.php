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

use function \is_array;

/**
 * Envelope factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'Envelope';

    const PRODUCT_TYPES = [
        'Decay'     => 'createDecay',
        'Shape'     => 'createShape',
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\Signal\IEnvelope {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown envelope type ' . $sType);
    }

    /**
     * Create the decay envelope type
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IEnvelope
     */
    private function createDecay(object $oDefinition, string $sType) : Audio\Signal\IEnvelope {
        $fInitial  = (float)($oDefinition->fInitial  ?? 1.0);
        $fTarget   = (float)($oDefinition->fTarget   ?? 0.0);
        $fHalfLife = (float)($oDefinition->fHalfLife ?? 1.0);
        return new DecayPulse(
            $fInitial,
            $fHalfLife,
            $fTarget
        );
    }

    /**
     * Create the shape envelope type
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IEnvelope
     */
    private function createShape(object $oDefinition, string $sType) : Audio\Signal\IEnvelope {
        if (!isset($oDefinition->aPoints) || !is_array($oDefinition->aPoints)) {
            throw new \RuntimeException('Shape envelope must have non empty points array');
        }
        return new Shape(
            (float)($oDefinition->fInitial ?? 0.0),
            $oDefinition->aPoints
        );
    }
}
