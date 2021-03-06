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

use function \is_object;

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
    ];


    /**
     * @inheritDoc
     */
    public function createFrom(\stdClass $oDefinition): Audio\Signal\IWaveform {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            /** @var callable $cCreator */
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    /**
     * Return one of the basic waveform types.
     *
     * @param  \stdClass $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IWaveform
     */
    private function createSimple(\stdClass $oDefinition, $sType): Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->bAliased) && $oDefinition->bAliased;
        switch ($sType) {
            case 'Sine':     return new Sine();
            case 'Triangle': return new Triangle();
            case 'Noise':    return new WhiteNoise();
            case 'Saw':      return new Saw();
            case 'Square':   return new Square();
        }
        throw new \RuntimeException('Unknown waveform type ' . $sType);
    }

    /**
     * Return the PWM waveform.
     *
     * @param  \stdClass $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IWaveform
     */
    private function createPulse(\stdClass $oDefinition, $sType): Audio\Signal\IWaveform {
        $bAliased = isset($oDefinition->bAliased) && $oDefinition->bAliased;

        // TODO - check for a PWM modulator definition in here

        return new Pulse();
    }


}
