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

class Factory implements Audio\IFactory {

    use Audio\TFactory;

    const STANDARD_KEY = 'machine';

    const PRODUCT_TYPES = [
        'multifm'    => 'createMultiOperatorFM',
    ];

    /**
     * @inheritDoc
     */
    public function createFrom(object $oDefinition) : Audio\IMachine {
        $sType    = strtolower($oDefinition->type ?? '<none>');
        $sFactory = self::PRODUCT_TYPES[$sType] ?? null;
        if ($sFactory) {
            $cCreator = [$this, $sFactory];
            return $cCreator($oDefinition, $sType);
        }
        throw new \RuntimeException('Unknown envelope type ' . $sType);
    }

    private function createMultiOperatorFM(object $oDefinition, string $sType) : Audio\IMachine {
        if (!isset($oDefinition->operators) || !is_array($oDefinition->operators)) {#
            throw new \RuntimeException('Missing operators section for FM synth');
        }
        $iNumOperators = count($oDefinition->operators);
        if ($iNumOperators < DeXter::MIN_OPERATORS || $iNumOperators > DeXter::MAX_OPERATORS) {
            throw new \RuntimeException('Invalid operator count');
        }
        $iPolyphony = (int)($oDefinition->voices ?? Audio\IMachine::MIN_POLYPHONY);

        echo "\tHave ", $iNumOperators, " operators and ", $iPolyphony, " voice(s)...\n";

        $oDexter    = new DeXter($iPolyphony, $iNumOperators);

        $iOperator  = 0;
        foreach ($oDefinition->operators as $oOperatorDefinition) {
            $this->configureMultiFMOperator($oDexter, $iOperator++, $oOperatorDefinition);
        }

        return $oDexter;
    }

    private function configureMultiFMOperator(DeXter $oDexter, int $iOperator, object $oDefinition) {
        $oDexter->selectOperator($iOperator);
        echo "\t\tConfiguring operator ", $iOperator, "...\n";
        if (isset($oDefinition->waveform)) {
            if (is_object($oDefinition->waveform)) {
                $oWaveform = Audio\Signal\Waveform\Factory::get()->createFrom($oDefinition->waveform);
                $oDexter->setWaveform($oWaveform);
                echo "\t\t\tSet custom waveform [", get_class($oWaveform), "].\n";
            } else {
                $iWaveform = (int)$oDefinition->waveform;
                $iModifier = (int)($oDefinition->modifier ?? Audio\Signal\Waveform\Rectifier::NONE);
                $oDexter->setEnumeratedWaveform($iWaveform, $iModifier);
                echo "\t\t\tSet standard waveform #", $iWaveform, ":", $iModifier, ".\n";
            }
        } else {
            echo "\t\t\tUsing default waveform.\n";
        }

        // Prefer semitones over absolute ratio
        if (isset($oDefinition->semitones)) {
            $oDexter->setRatioSemitones((float)$oDefinition->semitones);
            echo "\t\t\tSet ratio as ", ((float)$oDefinition->semitones), " semitones.\n";
        } else if (isset($oDefinition->ratio)) {
            $oDexter->setRatio((float)($oDefinition->ratio));
            echo "\t\t\tSet ratio as ", ((float)($oDefinition->ratio)), " absolute.\n";
        } else {
            echo "\t\t\tUsing default ratio.\n";
        }

        // Output mix level
        if (isset($oDefinition->outputmix)) {
            $oDexter->setOutputMixLevel((float)($oDefinition->outputmix));
            echo "\t\t\tSet output mix level to ", ((float)($oDefinition->outputmix)), ".\n";
        } else {
            echo "\t\t\tUsing default output mix level.\n";
        }

        // Level LFO
        if (isset($oDefinition->levellfo) && is_object($oDefinition->levellfo)) {
            $oDexter
                ->setLevelLFODepth((float)($oDefinition->levellfo->depth ?? 0.5))
                ->setLevelLFORate((float)($oDefinition->levellfo->rate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY))
                ->enableLevelLFO();
            echo "\t\t\tConfigured level LFO.\n";
        } else {
            $oDexter->disableLevelLFO();
            echo "\t\t\tNo level LFO configured.\n";
        }

        // Pitch LFO
        if (isset($oDefinition->pitchlfo) && is_object($oDefinition->pitchlfo)) {
            $oDexter
                ->setPitchLFODepth((float)($oDefinition->pitchlfo->depth ?? 0.5))
                ->setPitchLFORate((float)($oDefinition->pitchlfo->rate ?? Audio\Signal\Oscillator\LFO::DEF_FREQUENCY))
                ->enablePitchLFO();
            echo "\t\t\tConfigured level LFO.\n";
        } else {
            $oDexter->disablePitchLFO();
            echo "\t\t\tNo pitch LFO configured.\n";
        }

        // Level Envelope
        if (isset($oDefinition->levelenv) && is_object($oDefinition->levelenv)) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->levelenv);
            $oDexter->setLevelEnvelope($oEnvelope);
            echo "\t\t\tConfigured level envelope [", get_class($oEnvelope), "].\n";
        } else {
            echo "\t\t\tNo level envelope configured.\n";
        }

        // Pitch Envelope
        if (isset($oDefinition->pitchenv) && is_object($oDefinition->pitchenv)) {
            $oEnvelope = Audio\Signal\Envelope\Factory::get()->createFrom($oDefinition->pitchenv);
            $oDexter->setLevelEnvelope($oEnvelope);
            echo "\t\t\tConfigured pitch envelope [", get_class($oEnvelope), "].\n";
        } else {
            echo "\t\t\tNo pitch envelope configured.\n";
        }

        // Modulation Matrix
        if (!empty($oDefinition->modulators) && is_array($oDefinition->modulators)) {
            echo "\t\t\tConfuguring modulators...\n";
            foreach ($oDefinition->modulators as $oModulator) {
                if (is_object($oModulator) && isset($oModulator->source) && isset($oModulator->index)) {
                    $iSource = (int)$oModulator->source;
                    $fIndex  = (float)$oModulator->index;
                    $oDexter->setModulation($iSource, $fIndex);
                    echo "\t\t\t\tAdding modulation from operator ", $iSource, " at level ", $fIndex, ".\n";
                }
            }
        }
    }
}
