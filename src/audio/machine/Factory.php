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
use function ABadCafe\PDE\dprintf, \count, \get_class, \is_array, \is_object, \is_string;

/**
 * Machine factory
 */
class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'machine';

    const PRODUCT_TYPES = [
        'MonoBass'   => 'createMonoBass',
        'MultiFM'    => 'createMultiOperatorFM',
    ];

    const WAVEFORM_NAMES = [
        'Sine'       => Audio\Signal\IWaveform::SINE,
        'Triangle'   => Audio\Signal\IWaveform::TRIANGLE,
        'Saw'        => Audio\Signal\IWaveform::SAW,
        'Square'     => Audio\Signal\IWaveform::SQUARE,
        'Pulse'      => Audio\Signal\IWaveform::PULSE,
        'Noise'      => Audio\Signal\IWaveform::NOISE,
    ];

    const MODIFIER_NAMES = [
        'None'               => Audio\Signal\Waveform\Rectifier::NONE,
        'HalfWavePos'        => Audio\Signal\Waveform\Rectifier::HALF_RECT_P,
        'HalfWavePosScaled'  => Audio\Signal\Waveform\Rectifier::HALF_RECT_P_FS,
        'HalfWaveNeg'        => Audio\Signal\Waveform\Rectifier::HALF_RECT_N,
        'HalfWaveNegScaled'  => Audio\Signal\Waveform\Rectifier::HALF_RECT_N_FS,
        'FullWavePos'        => Audio\Signal\Waveform\Rectifier::FULL_RECT_P,
        'FullWavePosScaled'  => Audio\Signal\Waveform\Rectifier::FULL_RECT_P_FS,
        'FullWaveNeg'        => Audio\Signal\Waveform\Rectifier::FULL_RECT_N,
        'FullWaveNegScaled'  => Audio\Signal\Waveform\Rectifier::FULL_RECT_N_FS
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\IMachine {
        $sType    = $oDefinition->sType ?? '<none>';
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown machine type ' . $sType);
    }

    /**
     * Creates the Monophonic Bass Synth
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\IMachine
     */
    private function createMonoBass(object $oDefinition, string $sType) : Audio\IMachine {
        return new TBNaN;
    }

    /**
     * Creates the Multi Operator FM synth
     *
     * @param  object $oDefinition
     * @param  string $sType
     * @return Audio\IMachine
     */
    private function createMultiOperatorFM(object $oDefinition, string $sType) : Audio\IMachine {
        dprintf("Creating Multi Operator FM Machine...\n");
        if (!isset($oDefinition->aOperators) || !is_array($oDefinition->aOperators)) {#
            throw new \RuntimeException('Missing operators section for FM synth');
        }
        $iNumOperators = count($oDefinition->aOperators);
        if ($iNumOperators < DeXter::MIN_OPERATORS || $iNumOperators > DeXter::MAX_OPERATORS) {
            throw new \RuntimeException('Invalid operator count');
        }
        $iPolyphony = (int)($oDefinition->iMaxPolyphony ?? Audio\IMachine::MIN_POLYPHONY);

        dprintf(
            "\tHave %d operators and maximum %d note polyphony...\n",
            $iNumOperators,
            $iPolyphony
        );

        $oDexter = new DeXter($iPolyphony, $iNumOperators);

        $aOperatorNames = [];

        $iOperator  = 0;
        foreach ($oDefinition->aOperators as $oOperatorDefinition) {
            $this->configureMultiFMOperator($oDexter, $iOperator++, $oOperatorDefinition, $aOperatorNames);
        }

        return $oDexter;
    }

    private function configureMultiFMOperator(DeXter $oDexter, int $iOperator, object $oDefinition, array& $aOperatorNames) {
        $oDexter->selectOperator($iOperator);

        dprintf(
            "\t\tConfiguring operator %d...\n",
            $iOperator
        );

        if (!isset($oDefinition->sName)) {
            $aOperatorNames[(string)$iOperator] = $iOperator;
        } else {
            $aOperatorNames[(string)$oDefinition->sName] = $iOperator;
        }
        if (isset($oDefinition->Waveform)) {
            if (is_object($oDefinition->Waveform)) {
                $oWaveform = Audio\Signal\Waveform\Factory::get()->createFrom($oDefinition->Waveform);
                $oDexter->setWaveform($oWaveform);

                dprintf(
                    "\t\t\tSet custom Waveform [%s].\n",
                    get_class($oWaveform)
                );

            } else if (
                is_string($oDefinition->Waveform) &&
                isset(self::WAVEFORM_NAMES[$oDefinition->Waveform])
            ) {
                $iWaveform = self::WAVEFORM_NAMES[$oDefinition->Waveform];
                $iModifier = Audio\Signal\Waveform\Rectifier::NONE;
                if (
                    isset($oDefinition->sModifier) &&
                    is_string($oDefinition->sModifier) &&
                    isset(self::MODIFIER_NAMES[$oDefinition->sModifier])
                ) {
                    $iModifier = self::MODIFIER_NAMES[$oDefinition->sModifier];
                }
                $oDexter->setEnumeratedWaveform($iWaveform, $iModifier);

                dprintf(
                    "\t\t\tSet custom Waveform #%d with modifier #%d.\n",
                    $iWaveform,
                    $iModifier
                );

            }
        } else {
            dprintf(
                "\t\t\tUsing default Waveform.\n"
            );
        }

        // Prefer semitones over absolute ratio
        if (isset($oDefinition->fSemitones)) {
            $oDexter->setRatioSemitones((float)$oDefinition->fSemitones);

            dprintf(
                "\t\t\tSet ratio as %f semitones.\n",
                (float)$oDefinition->fSemitones
            );

        } else if (isset($oDefinition->fRatio)) {
            $oDexter->setRatio((float)($oDefinition->fRatio));

            dprintf(
                "\t\t\tSet ratio as %f absolute.\n",
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
                "\t\t\tSet Output Mix level to %f.\n",
                (float)($oDefinition->fOutputMix)
            );

        } else {
            dprintf(
                "\t\t\tUsing default Output Mix level.\n"
            );
        }

        // Level LFO
        if (isset($oDefinition->LevelLFO) && is_object($oDefinition->LevelLFO)) {
            $fDepth = (float)($oDefinition->LevelLFO->fDepth ?? 0.5);
            $fRate  = (float)($oDefinition->LevelLFO->fRate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY);
            $oDexter
                ->setLevelLFODepth($fDepth)
                ->setLevelLFORate($fRate)
                ->enableLevelLFO();
            dprintf(
                "\t\t\tConfigured Level LFO [Depth: %.f, Rate: %.f]\n",
                $fDepth,
                $fRate
            );
        } else {
            $oDexter->disableLevelLFO();
            dprintf(
                "\t\t\tNo Level LFO defined.\n"
            );
        }

        // Pitch LFO
        if (isset($oDefinition->PitchLFO) && is_object($oDefinition->PitchLFO)) {
            $fDepth = (float)($oDefinition->PitchLFO->fDepth ?? 0.5);
            $fRate  = (float)($oDefinition->PitchLFO->fRate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY);
            $oDexter
                ->setPitchLFODepth($fDepth)
                ->setPitchLFORate($fRate)
                ->enablePitchLFO();
            dprintf(
                "\t\t\tConfigured Pitch LFO [Depth: %.f, Rate: %.f]\n",
                $fDepth,
                $fRate
            );
        } else {
            $oDexter->disablePitchLFO();
            dprintf(
                "\t\t\tNo Pitch LFO defined.\n"
            );
        }

        // Level Envelope
        if (isset($oDefinition->LevelEnv) && is_object($oDefinition->LevelEnv)) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->LevelEnv);
            $oDexter->setLevelEnvelope($oEnvelope);

            // Velocity Dynamics for the level envelope level
            if (
                isset($oDefinition->LevelEnv->Velocity->Intensity) &&
                is_object($oDefinition->LevelEnv->Velocity->Intensity)
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->LevelEnv->Velocity->Intensity);
                $oDexter->setLevelIntensityVelocityCurve($oCurve);
            }

            // Velocity Dynamics for the level envelope speed
            if (
                isset($oDefinition->LevelEnv->Velocity->Rate) &&
                is_object($oDefinition->LevelEnv->Velocity->Rate)
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->LevelEnv->Velocity->Rate);
                $oDexter->setLevelRateVelocityCurve($oCurve);
            }

            dprintf(
                "\t\t\tConfigured Level Envelope [%s].\n",
                get_class($oEnvelope)
            );
        } else {
            dprintf(
                "\t\t\tNo Level Envelope configured.\n"
            );
        }

        // Pitch Envelope
        if (isset($oDefinition->PitchEnv) && is_object($oDefinition->PitchEnv)) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->PitchEnv);
            $oDexter->setLevelEnvelope($oEnvelope);

            // Velocity Dynamics for the pitch envelope level
            if (
                isset($oDefinition->PitchEnv->Velocity->Intensity) &&
                is_object($oDefinition->PitchEnv->Velocity->Intensity)
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->PitchEnv->Velocity->Intensity);
                $oDexter->setLevelIntensityVelocityCurve($oCurve);
            }

            // Velocity Dynamics for the pitch envelope speed
            if (
                isset($oDefinition->PitchEnv->Velocity->Rate) &&
                is_object($oDefinition->PitchEnv->Velocity->Rate)
            ) {
                $oCurve = Audio\ControlCurve\Factory::get()
                    ->createFrom($oDefinition->PitchEnv->Velocity->Rate);
                $oDexter->setLevelRateVelocityCurve($oCurve);
            }

            dprintf(
                "\t\t\tConfigured Pitch Envelope [%s].\n",
                get_class($oEnvelope)
            );
        } else {
            dprintf(
                "\t\t\tNo Pitch Envelope configured.\n"
            );
        }

        // Modulation Matrix
        if (!empty($oDefinition->aModulators) && is_array($oDefinition->aModulators)) {
            dprintf(
                "\t\t\tConfuguring modulators...\n"
            );
            foreach ($oDefinition->aModulators as $oModulator) {
                if (is_object($oModulator) && isset($oModulator->sSource) && isset($oModulator->fIndex)) {
                    $sSource = (string)$oModulator->sSource;
                    $fIndex  = (float)$oModulator->fIndex;
                    $oDexter->setModulation($aOperatorNames[$sSource], $fIndex);
                    dprintf(
                        "\t\t\t\tAdding modulation from Operator %s at level %.f\n",
                        $sSource,
                        $fIndex
                    );
                }
            }
        }
    }
}
