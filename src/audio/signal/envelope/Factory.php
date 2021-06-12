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

/**
 * Envelope factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'envelope';

    const PRODUCT_TYPES = [
        'decay'     => 'createDecay',
        'shape'     => 'createShape',
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\Signal\IEnvelope {
        $sType    = strtolower($oDefinition->type ?? '<none>');
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown envelope type ' . $sType);
    }

    private function createDecay(object $oDefinition, string $sType) : Audio\Signal\IEnvelope {
        $fInitial  = (float)($oDefinition->initial   ?? 1.0);
        $fTarget   = (float)($oDefinition->target    ?? 0.0);
        $fHalfLife = (float)($oDefinition->halflife  ?? 1.0);
        return new DecayPulse(
            $fInitial,
            $fHalfLife,
            $fTarget
        );
    }

    private function createShape(object $oDefinition, string $sType) : Audio\Signal\IEnvelope {
        if (!isset($oDefinition->points) || !is_array($oDefinition->points)) {
            throw new \RuntimeException('Shape envelope must have non empty points array');
        }
        return new Shape(
            (float)($oDefinition->initial ?? 0.0),
            $oDefinition->points
        );
    }
}
