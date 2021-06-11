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

namespace ABadCafe\PDE\Audio\Signal\Oscillator;

use ABadCafe\PDE\Audio;

/**
 * Oscillator factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'oscillator';

    const PRODUCT_TYPES = [
        'lfo'       => 'createLFO',
        'lfo1to0'   => 'createLFO',
        'lfo0to1'   => 'createLFO',
        'audio'     => 'createSound',
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\Signal\IOscillator {
        $sType    = strtolower($oDefinition->type ?? '<none>');
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown oscillator type ' . $sType);
    }

    private function createLFO(object $oDefinition, string $sType) : Audio\Signal\IOscillator {
        $fDepth = (float)($oDefinition->depth ?? 0.5);
        $fRate  = (float)($oDefinition->rate  ?? LFO::DEF_FREQUENCY);

        $oWaveform   = null;
        $sSubNodeKey = Audio\Signal\Waveform\Factory::STANDARD_KEY;

        if (
            !empty($oDefinition->{$sSubNodeKey}) &&
            is_object($oDefinition->{$sSubNodeKey})
        ) {
            $oWaveform = Audio\Signal\Waveform\Factory::get()
                ->createFrom($oDefinition->{$sSubNodeKey});
        }

        switch ($sType) {
            case 'lfo':     return new LFO($oWaveform, $fRate, $fDepth);
            case 'lfo1to0': return new LFOOneToZero($oWaveform, $fRate, $fDepth);
            case 'lfo0to1': return new LFOZeroToOne($oWaveform, $fRate, $fDepth);
            default:
                throw new \RuntimeException('Unknown LFO type ' . $sType);
        }
    }

    private function createSound(object $oDefinition, string $sType) :  Audio\Signal\IOscillator  {
        $oWaveform   = null;
        $sSubNodeKey = Audio\Signal\Waveform\Factory::STANDARD_KEY;

        $fDepth = (float)($oDefinition->depth ?? 0.5);
        $fPitch = (float)($oDefinition->pitch ?? Sound::DEF_FREQUENCY);

        if (
            !empty($oDefinition->{$sSubNodeKey}) &&
            is_object($oDefinition->{$sSubNodeKey})
        ) {
            $oWaveform = Audio\Signal\Waveform\Factory::get()
                ->createFrom($oDefinition->{$sSubNodeKey});
        } else {
            $oWaveform = new Audio\Signal\Waveform\Sine();
        }
        return new Sound($oWaveform, $fPitch, $fDepth);
    }
}
