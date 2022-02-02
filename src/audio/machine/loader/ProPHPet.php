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

namespace ABadCafe\PDE\Audio\Machine\Loader;
use ABadCafe\PDE\Audio;
use function ABadCafe\PDE\dprintf, \count, \get_class, \is_array, \is_string;

/**
 * Subtractive Synth factory
 */
class ProPHPet implements Audio\IFactory {

    /**
     * @inheritDoc
     */
    public function createFrom(\stdClass $oDefinition): Audio\Machine\ProPHPet {
        dprintf("\n%s() Creating %s...\n", __METHOD__, Audio\Machine\ProPHPet::class);
        $iVoices = (int)($oDefinition->iVoices ?? Audio\IMachine::MIN_POLYPHONY);

        $oMachine = new Audio\Machine\ProPHPet($iVoices);

        if (
            isset($oDefinition->Oscillator1) &&
            $oDefinition->Oscillator1 instanceof \stdClass
        ) {
            dprintf("\tConfiguring Oscillator 1...\n");
            $this->configureOscillator($oMachine, Audio\Machine\ProPHPet::TARGET_OSC_1, $oDefinition->Oscillator1);
        }

        if (
            isset($oDefinition->Oscillator2) &&
            $oDefinition->Oscillator2 instanceof \stdClass
        ) {
            dprintf("\tConfiguring Oscillator 2...\n");
            $this->configureOscillator($oMachine, Audio\Machine\ProPHPet::TARGET_OSC_2, $oDefinition->Oscillator2);
        }

        return $oMachine;
    }

    private function configureOscillator(Audio\Machine\ProPHPet $oMachine, int $iOscillator, \stdClass $oDefinition): void {
        $iWaveform = Audio\Machine\Factory::getEnumeratedWaveform($oDefinition);
        if (null !== $iWaveform) {
            $oMachine->assignEnumeratedWaveform($iWaveform, $iOscillator);
            dprintf(
                "\t\tSet Waveform %d.\n",
                $iWaveform
            );
        }
        // Prefer semitones over absolute ratio
        if (isset($oDefinition->fSemitones)) {
            $oMachine->setFrequencyRatioSemitones((float)$oDefinition->fSemitones, $iOscillator);
            dprintf(
                "\t\tSet frequency ratio as %f semitones.\n",
                (float)$oDefinition->fSemitones
            );

        } else if (isset($oDefinition->fRatio)) {
            $oMachine->setFrequencyRatio((float)($oDefinition->fRatio), $iOscillator);
            dprintf(
                "\t\tSet frequency ratio as %f absolute.\n",
                (float)$oDefinition->fRatio
            );
        }
        if (isset($oDefinition->fLevel)) {
            $oMachine->setLevel((float)($oDefinition->fLevel), $iOscillator);
            dprintf(
                "\t\tSet Output Level to %f.\n",
                (float)($oDefinition->fLevel)
            );
        }
    }
}
