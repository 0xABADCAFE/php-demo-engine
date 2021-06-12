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

namespace ABadCafe\PDE\Audio\Signal\Waveform;
use ABadCafe\PDE\Audio;

/**
 * Waveform factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'waveform';

    const PRODUCT_TYPES = [
        'sine'      => 'createSimple',
        'triangle'  => 'createSimple',
        'saw'       => 'createSimple',
        'square'    => 'createSimple',
        'noise'     => 'createSimple',
        'pulse'     => 'createPulse',
        'rectifier' => 'createRectifier'
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\Signal\IWaveform {
        $sType    = strtolower($oDefinition->type ?? '<none>');
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    private function createSimple(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->aliased) && $oDefinition->aliased;
        switch ($sType) {
            case 'sine':     return new Sine();
            case 'triangle': return new Triangle();
            case 'noise':    return new WhiteNoise();
            case 'saw':      return $bAliased ? new AliasedSaw()    : new Saw();
            case 'square':   return $bAliased ? new AliasedSquare() : new Square();
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    private function createPulse(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->aliased) && $oDefinition->aliased;

        // TODO - check for a PWM modulator definition in here

        return $bAliased ? new AliasedPulse() : new Pulse();
    }

    private function createRectifier(object $oDefinition, $sType) {
        if (empty($oDefinition->{self::STANDARD_KEY}) || !is_object($oDefinition->{self::STANDARD_KEY})) {
            throw new \RuntimeException('Rectifier requires a waveform');
        }

        if (!empty($oDefinition->preset)) {
            $iPreset = (int)$oDefinition->preset;
            return Rectifier::createStandard($this->createFrom($oDefinition->{self::STANDARD_KEY}), $iPreset);
        }

        $fMinLevel = (float)($oDefinition->minLevel ?? -1.0);
        $fMaxLevel = (float)($oDefinition->maxLevel ?? 1.0);
        $fScale    = (float)($oDefinition->scale ?? 1.0);
        $fBias     = (float)($oDefinition->bias ?? 0.0);
        $bFold     = (bool)($oDefinition->fold ?? false);

        return new Rectifier(
            $this->createFrom($oDefinition->{self::STANDARD_KEY}),
            $fMinLevel,
            $fMaxLevel,
            $bFold,
            $fScale,
            $fBias
        );
    }
}
