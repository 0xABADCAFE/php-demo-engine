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
 * TBNaN Loader
 *
 * Constricts and parameterises a TBNaN instance from a definition property crate.
 */
class TBNaN implements Audio\IFactory {

    /**
     * @inheritDoc
     */
    public function createFrom(\stdClass $oDefinition): Audio\Machine\TBNaN {
        dprintf("\n%s() Creating %s...\n", __METHOD__, Audio\Machine\TBNaN::class);
        $oBass = new Audio\Machine\TBNaN;

        dprintf("\n%s() Configuring %s...\n", __METHOD__, Audio\Machine\TBNaN::class);

        $this->configureOscillator($oBass, $oDefinition);
        $this->configureEnvelope($oBass, $oDefinition);
        $this->configureFilter($oBass, $oDefinition);

        return $oBass;
    }

    private function configureOscillator(Audio\Machine\TBNaN $oBass, \stdClass $oDefinition): void {
        // Waveform
        $iWaveform = Audio\Machine\Factory::getEnumeratedWaveform($oDefinition);
        if (null !== $iWaveform) {
            $oBass->setEnumeratedWaveform($iWaveform);
        }

        // Pulsewidth
        if (
            isset($oDefinition->Pulse) &&
            $oDefinition->Pulse instanceof \stdClass
        ) {
            if (isset($oDefinition->Pulse->fWidth)) {
                dprintf("\tGot Pulse > fWidth %s\n", $oDefinition->Pulse->fWidth);
                $oBass->setPWMWidth((float)$oDefinition->Pulse->fWidth);
            }
            if (isset($oDefinition->Pulse->fRate)) {
                dprintf("\tGot Pulse > fRate %s\n", $oDefinition->Pulse->fRate);
                $oBass->setPWMLFORate((float)$oDefinition->Pulse->fRate);
            }
        }
    }

    private function configureEnvelope(Audio\Machine\TBNaN $oBass, \stdClass $oDefinition): void {
        if (
            isset($oDefinition->Level) &&
            $oDefinition->Level instanceof \stdClass
        ) {
            if (isset($oDefinition->Level->fDecay)) {
                dprintf("\tGot Level > fDecay %s\n", $oDefinition->Level->fDecay);
                $oBass->setLevelDecay((float)$oDefinition->Level->fDecay);
            }
            if (isset($oDefinition->Level->fTarget)) {
                dprintf("\tGot Level > fTarget %s\n", $oDefinition->Level->fTarget);
                $oBass->setLevelTarget((float)$oDefinition->Level->fTarget);
            }
        }
    }

    private function configureFilter(Audio\Machine\TBNaN $oBass, \stdClass $oDefinition): void {
        // Filter
        if (
            isset($oDefinition->Filter) &&
            $oDefinition->Filter instanceof \stdClass
        ) {
            if (isset($oDefinition->Filter->fCutoff)) {
                dprintf("\tGot Filter > fCutoff %s\n", $oDefinition->Filter->fCutoff);
                $oBass->setCutoff((float)$oDefinition->Filter->fCutoff);
            }
            if (isset($oDefinition->Filter->fResonance)) {
                dprintf("\tGot Filter > fResonance %s\n", $oDefinition->Filter->fResonance);
                $oBass->setResonance((float)$oDefinition->Filter->fResonance);
            }
            if (isset($oDefinition->Filter->fDecay)) {
                dprintf("\tGot Filter > fDecay %s\n", $oDefinition->Filter->fDecay);
                $oBass->setCutoffDecay((float)$oDefinition->Filter->fDecay);
            }
            if (isset($oDefinition->Filter->fTarget)) {
                dprintf("\tGot Level > fTarget %s\n", $oDefinition->Filter->fTarget);
                $oBass->setCutoffTarget((float)$oDefinition->Filter->fTarget);
            }
        }
    }
}
