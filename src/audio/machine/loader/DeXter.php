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
 * DeXter Loader
 *
 * Constricts and parameterises a DeXter instance from a definition property crate.
 */
class DeXter implements Audio\IFactory {

    /**
     * @inheritDoc
     */
    public function createFrom(\stdClass $oDefinition): Audio\Machine\DeXter {
        dprintf("\n%s() Creating %s...\n", __METHOD__, Audio\Machine\DeXter::class);

        if (!isset($oDefinition->Operators) || !is_array($oDefinition->Operators)) {#
            throw new \RuntimeException('Missing Operators for DeXter');
        }
        $iNumOperators = count($oDefinition->Operators);
        if (
            $iNumOperators < Audio\Machine\DeXter::MIN_OPERATORS ||
            $iNumOperators > Audio\Machine\DeXter::MAX_OPERATORS
        ) {
            throw new \RuntimeException('Invalid Operator count ' . $iNumOperators);
        }
        $iVoices = (int)($oDefinition->iVoices ?? Audio\IMachine::MIN_POLYPHONY);

        dprintf(
            "\tHave %d operators and %d note polyphony...\n",
            $iNumOperators,
            $iVoices
        );

        $oDexter = new Audio\Machine\DeXter($iVoices, $iNumOperators);

        $aOperatorNames = [];

        $iOperator  = 0;
        foreach ($oDefinition->Operators as $oOperatorDefinition) {
            $this->configureOperator($oDexter, $iOperator++, $oOperatorDefinition, $aOperatorNames);
        }

        return $oDexter;
    }



    /**
     * @param array<string, int> $aOperatorNames
     */
    private function configureOperator(Audio\Machine\DeXter $oDexter, int $iOperator, \stdClass $oDefinition, array& $aOperatorNames): void {
        $oDexter->selectOperator($iOperator);

        dprintf(
            "\tConfiguring operator %d...\n",
            $iOperator
        );

        if (!isset($oDefinition->sName)) {
            $aOperatorNames[(string)$iOperator] = $iOperator;
        } else {
            $aOperatorNames[(string)$oDefinition->sName] = $iOperator;
        }

        $iWaveform = Audio\Machine\Factory::getEnumeratedWaveform($oDefinition);
        if (null !== $iWaveform) {
            $oDexter->setEnumeratedWaveform($iWaveform);

            dprintf(
                "\t\tSet Waveform %d.\n",
                $iWaveform
            );

        } else {
            dprintf(
                "\t\tUsing default Waveform.\n"
            );
        }

        // Prefer semitones over absolute ratio
        if (isset($oDefinition->fSemitones)) {
            $oDexter->setRatioSemitones((float)$oDefinition->fSemitones);

            dprintf(
                "\t\tSet ratio as %f semitones.\n",
                (float)$oDefinition->fSemitones
            );

        } else if (isset($oDefinition->fRatio)) {
            $oDexter->setRatio((float)($oDefinition->fRatio));

            dprintf(
                "\t\tSet ratio as %f absolute.\n",
                (float)$oDefinition->fRatio
            );

        } else {
            dprintf(
                "\t\t\tUsing default ratio of 1.0.\n"
            );
        }

        // Output mix level
        if (isset($oDefinition->fOutputMix)) {
            $oDexter->setOutputMixLevel((float)($oDefinition->fOutputMix));

            dprintf(
                "\t\tSet Output Mix level to %f.\n",
                (float)($oDefinition->fOutputMix)
            );

        } else {
            dprintf(
                "\t\tUsing default Output Mix level.\n"
            );
        }

        // Level LFO
        if (isset($oDefinition->LevelLFO) && $oDefinition->LevelLFO instanceof \stdClass) {
            $fDepth = (float)($oDefinition->LevelLFO->fDepth ?? 0.5);
            $fRate  = (float)($oDefinition->LevelLFO->fRate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY);
            $oDexter
                ->setLevelLFODepth($fDepth)
                ->setLevelLFORate($fRate)
                ->enableLevelLFO();
            dprintf(
                "\t\tConfigured Level LFO [Depth: %.f, Rate: %.f]\n",
                $fDepth,
                $fRate
            );
        } else {
            $oDexter->disableLevelLFO();
            dprintf(
                "\t\tNo Level LFO defined.\n"
            );
        }

        // Pitch LFO
        if (isset($oDefinition->PitchLFO) && $oDefinition->PitchLFO instanceof \stdClass) {
            $fDepth = (float)($oDefinition->PitchLFO->fDepth ?? 0.5);
            $fRate  = (float)($oDefinition->PitchLFO->fRate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY);
            $oDexter
                ->setPitchLFODepth($fDepth)
                ->setPitchLFORate($fRate)
                ->enablePitchLFO();
            dprintf(
                "\t\tConfigured Pitch LFO [Depth: %.f, Rate: %.f]\n",
                $fDepth,
                $fRate
            );
        } else {
            $oDexter->disablePitchLFO();
            dprintf(
                "\t\tNo Pitch LFO defined.\n"
            );
        }

        // Level Envelope
        if (isset($oDefinition->LevelEnv) && $oDefinition->LevelEnv instanceof \stdClass) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->LevelEnv);
            $oDexter->setLevelEnvelope($oEnvelope);

            // Velocity Dynamics for the level envelope level
            if (
                isset($oDefinition->LevelEnv->Velocity->Intensity) &&
                $oDefinition->LevelEnv->Velocity->Intensity instanceof \stdClass
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->LevelEnv->Velocity->Intensity);
                $oDexter->setLevelIntensityVelocityCurve($oCurve);
            }

            // Velocity Dynamics for the level envelope speed
            if (
                isset($oDefinition->LevelEnv->Velocity->Rate) &&
                $oDefinition->LevelEnv->Velocity->Rate instanceof \stdClass
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->LevelEnv->Velocity->Rate);
                $oDexter->setLevelRateVelocityCurve($oCurve);
            }

            dprintf(
                "\t\tConfigured Level Envelope [%s].\n",
                get_class($oEnvelope)
            );
        } else {
            dprintf(
                "\t\tNo Level Envelope configured.\n"
            );
        }

        // Pitch Envelope
        if (isset($oDefinition->PitchEnv) && $oDefinition->PitchEnv instanceof \stdClass) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->PitchEnv);
            $oDexter->setLevelEnvelope($oEnvelope);

            // Velocity Dynamics for the pitch envelope level
            if (
                isset($oDefinition->PitchEnv->Velocity->Intensity) &&
                $oDefinition->PitchEnv->Velocity->Intensity instanceof \stdClass
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->PitchEnv->Velocity->Intensity);
                $oDexter->setLevelIntensityVelocityCurve($oCurve);
            }

            // Velocity Dynamics for the pitch envelope speed
            if (
                isset($oDefinition->PitchEnv->Velocity->Rate) &&
                $oDefinition->PitchEnv->Velocity->Rate instanceof \stdClass
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->PitchEnv->Velocity->Rate);
                $oDexter->setLevelRateVelocityCurve($oCurve);
            }

            dprintf(
                "\t\tConfigured Pitch Envelope [%s].\n",
                get_class($oEnvelope)
            );
        } else {
            dprintf(
                "\t\tNo Pitch Envelope configured.\n"
            );
        }

        // Modulation Matrix
        if (!empty($oDefinition->aModulators) && is_array($oDefinition->aModulators)) {
            dprintf(
                "\t\tConfuguring modulators...\n"
            );
            foreach ($oDefinition->aModulators as $oModulator) {
                if (
                    $oModulator instanceof \stdClass &&
                    isset($oModulator->sSource) &&
                    isset($oModulator->fIndex)
                ) {
                    $sSource = (string)$oModulator->sSource;
                    $fIndex  = (float)$oModulator->fIndex;
                    $oDexter->setModulation($aOperatorNames[$sSource], $fIndex);
                    dprintf(
                        "\t\tAdding modulation from Operator %s at level %.f\n",
                        $sSource,
                        $fIndex
                    );
                }
            }
        }
    }
}
