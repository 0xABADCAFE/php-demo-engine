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

use function \is_object;

/**
 * Oscillator factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'Oscillator';

    const PRODUCT_TYPES = [
        'LFO'          => 'createLFO',
        'LFOOneToZero' => 'createLFO',
        'LFOZeroToOne' => 'createLFO',
        'Audio'        => 'createSound',
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition): Audio\Signal\IOscillator {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown oscillator type ' . $sType);
    }

    /**
     * Create an LFO
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IOscillator
     */
    private function createLFO(object $oDefinition, string $sType): Audio\Signal\IOscillator {
        $fDepth      = (float)($oDefinition->fDepth ?? 0.5);
        $fRate       = (float)($oDefinition->fRate  ?? LFO::DEF_FREQUENCY);
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
            case 'LFO':          return new LFO($oWaveform, $fRate, $fDepth);
            case 'LFOOneToZero': return new LFOOneToZero($oWaveform, $fRate, $fDepth);
            case 'LFOZeroToOne': return new LFOZeroToOne($oWaveform, $fRate, $fDepth);
            default:
                throw new \RuntimeException('Unknown LFO type ' . $sType);
        }
    }

    /**
     * Create an audio oscillator
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\Signal\IOscillator
     */
    private function createSound(object $oDefinition, string $sType):  Audio\Signal\IOscillator  {
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
