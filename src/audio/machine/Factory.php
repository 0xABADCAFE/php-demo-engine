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

    const WAVEFORM_NAMES = [
        'sine'       => Audio\Signal\IWaveform::SINE,
        'triangle'   => Audio\Signal\IWaveform::TRIANGLE,
        'saw'        => Audio\Signal\IWaveform::SAW,
        'square'     => Audio\Signal\IWaveform::SQUARE,
        'pulse'      => Audio\Signal\IWaveform::PULSE,
        'noise'      => Audio\Signal\IWaveform::NOISE,
    ];

    const MODIFIER_NAMES = [
        'none'                => Audio\Signal\Waveform\Rectifier::NONE,
        'halfwave_pos'        => Audio\Signal\Waveform\Rectifier::HALF_RECT_P,
        'halfwave_pos_scaled' => Audio\Signal\Waveform\Rectifier::HALF_RECT_P_FS,
        'halfwave_neg'        => Audio\Signal\Waveform\Rectifier::HALF_RECT_N,
        'halfwave_neg_scaled' => Audio\Signal\Waveform\Rectifier::HALF_RECT_N_FS,
        'fullwave_pos'        => Audio\Signal\Waveform\Rectifier::FULL_RECT_P,
        'fullwave_pos_scaled' => Audio\Signal\Waveform\Rectifier::FULL_RECT_P_FS,
        'fullwave_neg'        => Audio\Signal\Waveform\Rectifier::FULL_RECT_N,
        'fullwave_neg_scaled' => Audio\Signal\Waveform\Rectifier::FULL_RECT_N_FS
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

        $aOperatorNames = [];

        $iOperator  = 0;
        foreach ($oDefinition->operators as $oOperatorDefinition) {
            $this->configureMultiFMOperator($oDexter, $iOperator++, $oOperatorDefinition, $aOperatorNames);
        }

        return $oDexter;
    }

    private function configureMultiFMOperator(DeXter $oDexter, int $iOperator, object $oDefinition, array& $aOperatorNames) {
        $oDexter->selectOperator($iOperator);
        echo "\t\tConfiguring operator ", $iOperator, "...\n";
        if (!isset($oDefinition->name)) {
            $aOperatorNames[(string)$iOperator] = $iOperator;
        } else {
            $aOperatorNames[(string)$oDefinition->name] = $iOperator;
        }
        if (isset($oDefinition->waveform)) {
            if (is_object($oDefinition->waveform)) {
                $oWaveform = Audio\Signal\Waveform\Factory::get()->createFrom($oDefinition->waveform);
                $oDexter->setWaveform($oWaveform);
                echo "\t\t\tSet custom waveform [", get_class($oWaveform), "].\n";
            } else if (
                is_string($oDefinition->waveform) &&
                isset(self::WAVEFORM_NAMES[$oDefinition->waveform])
            ) {
                $iWaveform = self::WAVEFORM_NAMES[$oDefinition->waveform];
                $iModifier = Audio\Signal\Waveform\Rectifier::NONE;
                if (
                    isset($oDefinition->modifier) &&
                    is_string($oDefinition->modifier) &&
                    isset(self::MODIFIER_NAMES[$oDefinition->modifier])
                ) {
                    $iModifier = self::MODIFIER_NAMES[$oDefinition->modifier];
                }
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
                    $sSource = (string)$oModulator->source;
                    $fIndex  = (float)$oModulator->index;
                    $oDexter->setModulation($aOperatorNames[$sSource], $fIndex);
                    echo "\t\t\t\tAdding modulation from operator ", $sSource, " at level ", $fIndex, ".\n";
                }
            }
        }
    }
}
