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

    const STANDARD_KEY = 'Waveform';

    const PRODUCT_TYPES = [
        'Sine'      => 'createSimple',
        'Triangle'  => 'createSimple',
        'Saw'       => 'createSimple',
        'Square'    => 'createSimple',
        'Noise'     => 'createSimple',
        'Pulse'     => 'createPulse',
        'Rectifier' => 'createRectifier',
        'Mutator'   => 'createMutator'
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\Signal\IWaveform {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    private function createSimple(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->bAliased) && $oDefinition->bAliased;
        switch ($sType) {
            case 'Sine':     return new Sine();
            case 'Triangle': return new Triangle();
            case 'Noise':    return new WhiteNoise();
            case 'Saw':      return $bAliased ? new AliasedSaw()    : new Saw();
            case 'Square':   return $bAliased ? new AliasedSquare() : new Square();
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    private function createPulse(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->bAliased) && $oDefinition->bAliased;

        // TODO - check for a PWM modulator definition in here

        return $bAliased ? new AliasedPulse() : new Pulse();
    }

    private function createRectifier(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        if (empty($oDefinition->{self::STANDARD_KEY}) || !is_object($oDefinition->{self::STANDARD_KEY})) {
            throw new \RuntimeException('Rectifier requires a waveform');
        }

        if (!empty($oDefinition->iPreset)) {
            $iPreset = (int)$oDefinition->iPreset;
            return Rectifier::createStandard($this->createFrom($oDefinition->{self::STANDARD_KEY}), $iPreset);
        }

        $fMinLevel = (float)($oDefinition->fMinLevel ?? -1.0);
        $fMaxLevel = (float)($oDefinition->fMaxLevel ?? 1.0);
        $fScale    = (float)($oDefinition->fScale    ?? 1.0);
        $fBias     = (float)($oDefinition->fBias     ?? 0.0);
        $bFold     = (bool)($oDefinition->bFold      ?? false);

        return new Rectifier(
            $this->createFrom($oDefinition->{self::STANDARD_KEY}),
            $fMinLevel,
            $fMaxLevel,
            $bFold,
            $fScale,
            $fBias
        );
    }

    private function createMutator(object $oDefinition, $sType) : Audio\Signal\IWaveform {
        if (empty($oDefinition->{self::STANDARD_KEY}) || !is_object($oDefinition->{self::STANDARD_KEY})) {
            throw new \RuntimeException('Mutator requires a waveform');
        }
        return new QuadrantMutator(
            $this->createFrom($oDefinition->{self::STANDARD_KEY})
        );
    }
}
