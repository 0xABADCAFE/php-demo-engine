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

namespace ABadCafe\PDE\Audio\Machine;
use ABadCafe\PDE\Audio;
use function ABadCafe\PDE\dprintf, \count, \get_class, \is_array, \is_string;

/**
 * Machine factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'machine';

    const STANDARD_KEY_WAVE = 'sWave';

    /**
     * Simple products are instantiated directly by helper functions
     */
    private const SIMPLE_PRODUCT_TYPES = [
        // Drum machine
        'trnan'      => 'createTRNaN', // Actual name
        'drum'       => 'createTRNaN', // Alias

        // Chiptune machine
        'ayesid'    =>  'createAYeSID',
        'chip'       => 'createAYeSID',
    ];

    /**
     * More complex products have their own dedicated factories.
     */
    private const COMPLEX_PRODUCT_TYPES = [
        // Bass machine
        'tbnan'      => Loader\TBNaN::class, // Actual name
        'bass'       => Loader\TBNaN::class, // Alias

        // Subtractive synth machine
        'prophpet'   => Loader\ProPHPet::class,
        'sub'        => Loader\ProPHPet::class,

        // FM Synth
        'dexter'    => Loader\DeX7er::class,
        'dex7er'    => Loader\DeX7er::class,
        'multifm'   => Loader\DeX7er::class,
    ];


    /**
     * @inheritDoc
     */
    public function createFrom(\stdClass $oDefinition): Audio\IMachine {
        $sType    = strtolower($oDefinition->sType ?? '<none>');
        $sFactory = self::SIMPLE_PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            /** @var callable $cCreator */
            $cCreator = [$this, $sFactory];
            return $this->applyStandardProperties(
                $cCreator($oDefinition, $sType),
                $oDefinition
            );
        }
        $sFactory = self::COMPLEX_PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            return $this->applyStandardProperties(
                (new $sFactory)->createFrom($oDefinition),
                $oDefinition
            );
        }
        throw new \RuntimeException('Unknown machine type ' . $sType);
    }

    /**
     * Helper function to turn a patch format wave name into it's enumerated equivalent. Accepts a definition property
     * crate and the expected field name and returns the corresponding enumerated wave, if any.
     *
     * @param  \stdClass $oDefinition
     * @param  string    $sField
     * @return int|null
     */
    public static function getEnumeratedWaveform(\stdClass $oDefinition, string $sField = self::STANDARD_KEY_WAVE): ?int {
        if (
            isset($oDefinition->{$sField}) &&
            is_string($oDefinition->{$sField})
        ) {
            dprintf("\tGot %s %s\n", $sField, $oDefinition->{$sField});
            $sName = $oDefinition->{$sField};
            return Audio\Signal\Waveform\Flyweight::WAVE_NAME_MAP[$sName] ?? null;
        }
        return null;
    }

    /**
     * Creates the Monophonic Bass Synth
     *
     * @param  \stdClass $oDefinition
     * @param  string $sType
     * @return Audio\IMachine
     */
    private function createTRNaN(\stdClass $oDefinition, string $sType): Audio\IMachine {
        dprintf("\n%s() Creating %s...\n", __METHOD__, TBNaN::class);
        return new TRNaN;
    }

    /**
     * Creates the Monophonic Bass Synth
     *
     * @param  \stdClass $oDefinition
     * @param  string $sType
     * @return Audio\IMachine
     */
    private function createTBNaN(\stdClass $oDefinition, string $sType): Audio\IMachine {
        dprintf("\n%s() Creating %s...\n", __METHOD__, TBNaN::class);
        $oBass = new TBNaN;

        dprintf("\n%s() Configuring %s...\n", __METHOD__, TBNaN::class);

        // Waveform
        $iWaveform = self::getEnumeratedWaveform($oDefinition);
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

        // Amp
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
        return $oBass;
    }

    private function createAYeSID(\stdClass $oDefinition, string $sType): Audio\IMachine {
        dprintf("\n%s() Creating %s...\n", __METHOD__, AYeSID::class);

        return new AYeSID(4);
    }

    private function applyStandardProperties(Audio\IMachine $oMachine, \stdClass $oDefinition): Audio\IMachine {
        if (isset($oDefinition->fOutputLevel)) {
            dprintf("\tGot fOutputLevel %s\n", $oDefinition->fOutputLevel);
            $oMachine->setOutputLevel((float)$oDefinition->fOutputLevel);
        }
        return $oMachine;
    }
}
