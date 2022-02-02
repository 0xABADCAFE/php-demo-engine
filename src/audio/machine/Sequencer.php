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

use \OutOfBoundsException;
use \RangeException;
use function ABadCafe\PDE\dprintf, \cos, \array_filter, \array_map, \array_unique, \floor, \max, \min, \microtime, \printf;

/**
 * Sequencer
 *
 * Main song sequencer. Defines the tempo, time signature, measure size etc., maintains a set of IMachine
 * instances, the Patterns for them and sequence of those Patterns per Machine. Provided methods are meant to
 * facilitate the construction of a song sequence from an external definition.
 */
class Sequencer {
    const
        MIN_TEMPO_BPM            = 32,
        DEF_TEMPO_BPM            = 120,
        MAX_TEMPO_BPM            = 240,
        MIN_BEATS_PER_MEASURE    = 3,
        DEF_BEATS_PER_MEASURE    = 4,
        MAX_BEATS_PER_MEASURE    = 16,
        MIN_LINES_PER_BEAT       = 1,
        DEF_LINES_PER_BEAT       = 4,
        MAX_LINES_PER_BEAT       = 32,
        DEF_PATTERN_LENGTH       = self::DEF_BEATS_PER_MEASURE * self::DEF_LINES_PER_BEAT
    ;

    private int
        $iTempoBeatsPerMinute = self::DEF_TEMPO_BPM,
        $iBeatsPerMeasure     = self::DEF_BEATS_PER_MEASURE,
        $iLinesPerBeat        = self::DEF_LINES_PER_BEAT,
        $iBasePatternLength   = self::DEF_PATTERN_LENGTH,
        $iNumMeasures         = 0,
        $iSwingLines          = 1
    ;

    private float
        $fSwingDepth          = 0.0
    ;

    /** @var array<string, Audio\IMachine> $aMachines */
    private array $aMachines = [];

    /** @var array<string, Audio\Sequence\Pattern[]> $aMachinePatterns */
    private array $aMachinePatterns = [];

    /** @var array<string, int> $aMachinePatternLabels */
    private array $aMachinePatternLabels;

    /**
     * @var array<string, array<int, Audio\Sequence\Pattern>> $aMachineSequences
     */
    private array $aMachineSequences = [];


    /**
     * Set the tempo, in beats per minute.
     *
     * @param  int $iTempoBeatsPerMinute
     * @return self
     */
    public function setTempo(int $iTempoBeatsPerMinute): self {
        $this->iTempoBeatsPerMinute = min(
            max($iTempoBeatsPerMinute, self::MIN_TEMPO_BPM),
            self::MAX_TEMPO_BPM
        );
        return $this;
    }

    public function setSwing(float $fDepth, int $iLines): self {
        $this->fSwingDepth = $fDepth;
        $this->iSwingLines = max(1, $iLines);
        return $this;
    }

    /**
     * Get the tempo, in beats per minute.
     *
     * @return int
     */
    public function getTempo(): int {
        return $this->iTempoBeatsPerMinute;
    }

    /**
     * Set the expected beats per measure.
     *
     * @param  int $iBeatsPerMeasure
     * @return self
     */
    public function setBeatsPerMeasure(int $iBeatsPerMeasure): self {
        $this->iBeatsPerMeasure = min(
            max($iBeatsPerMeasure, self::MIN_BEATS_PER_MEASURE),
            self::MAX_BEATS_PER_MEASURE
        );
        $this->iBasePatternLength = $this->iBeatsPerMeasure * $this->iLinesPerBeat;
        return $this;
    }

    /**
     * Get the beats per measure.
     *
     * @return int
     */
    public function getBeatsPerMeasure(): int {
        return $this->iBasePatternLength;
    }

    /**
     * Set the lines per beat as applied to any Patterns.
     *
     * @param  int $iLinesPerBeat
     * @return self
     */
    public function setLinesPerBeat(int $iLinesPerBeat): self {
        $this->iLinesPerBeat = min(
            max($iLinesPerBeat, self::MIN_LINES_PER_BEAT),
            self::MAX_LINES_PER_BEAT
        );
        $this->iBasePatternLength = $this->iBeatsPerMeasure * $this->iLinesPerBeat;
        return $this;
    }

    /**
     * Get the lines per beat.
     *
     * @return int
     */
    public function getLinesPerBeat(): int {
        return $this->iLinesPerBeat;
    }

    /**
     * Get the total sequence length, in measures.
     *
     * @return int
     */
    public function getLength(): int {
        return $this->iNumMeasures;
    }

    /**
     * Add a named Machine. Patterns can be added to the same name which will be assigned to that machine.
     *
     * @param  string         $sMachineName
     * @param  Audio\IMachine $oMachine
     * @return self
     * @throws \LogicException Thrown if the machine name has already been assigned.
     */
    public function addMachine(string $sMachineName, Audio\IMachine $oMachine): self {
        if (!isset($this->aMachines[$sMachineName])) {
            $this->aMachines[$sMachineName]             = $oMachine;
            $this->aMachinePatterns[$sMachineName]      = [];
            $this->aMachineSequences[$sMachineName]     = [];
            $this->aMachinePatternLabels[$sMachineName] = 0;
        } else {
            throw new \LogicException('Machine "' . $sMachineName . '" already in use');
        }
        return $this;
    }

    /**
     * Creates a new Pattern for the given Machine. The pattern will have as many channels as the
     * Machine has voices and a length of 1 measure by default. Accepts an optional array of measures
     * for the machine in which to slot the newly created pattern.
     *
     * @param  string                 $sMachineName - Which machine to create the Pattern for
     * @param  int[]|null             $aMeasures    - Which measures in the sequence should use the Pattern
     * @return Audio\Sequence\Pattern
     * @throws OutOfBoundsException   - When the machine name is not recognised
     * @throws RangeException         - When the Pattern length is less than 1 measure
     */
    public function allocatePattern(string $sMachineName, ?array $aMeasures = null): Audio\Sequence\Pattern {
        $this->assertMachineExists($sMachineName);

        // Create the Pattern.
        $oPattern = new Audio\Sequence\Pattern(
            $this->aMachines[$sMachineName]->getNumVoices(),
            $this->iBasePatternLength,
            $sMachineName . '_' . $this->aMachinePatternLabels[$sMachineName]++
        );

        // Associate it with the machine in question
        $this->addPattern($sMachineName, $oPattern);

        if (null !== $aMeasures) {
            $this->populateMachineSequence($sMachineName, $oPattern, $aMeasures);
        }

        return $oPattern;
    }

    /**
     * Set up a sequence of Patterns for a named machine. The sequence is an array of measure positions
     * where the Pattern will be used. Note that the Pattern can be null, in which case, any patterns
     * in those positions will be cleared. Throw an exception if the machine name is unrecognised or the
     * Pattern has more channels than the machine has voices. While it is expected that the Pattern will
     * be reused with the machine it was created for, this is not mandatory and can be played on any machine
     * that has enough polyphony.
     *
     * @param  string                      $sMachineName - Which machine to insert the patterns for
     * @param  Audio\Sequence\Pattern|null $oPattern     - Pattern to use or null to clear
     * @param  int[]                       $aMeasures    - Which measures in the sequence should use the Pattern
     * @return self
     * @throws OutOfBoundsException        - When the machine name is not recognised
     * @throws RangeException              - When the pattern has more channels than the Machine has voices.
     */
    public function populateMachineSequence(
        string $sMachineName,
        ?Audio\Sequence\Pattern $oPattern,
        array $aMeasures
    ): self {
        $this->assertMachineExists($sMachineName);

        // Sanitise the sequence. Dedupe, cast to int and remove any negative measure positions
        $aMeasures = array_filter(
            array_map(
                'intval',
                array_unique($aMeasures)
            ),
            function (int $i) {
                return $i >= 0;
            }
        );
        if (!empty($aMeasures)) {

            // Keep track of the total sequence length, in measures.
            $this->iNumMeasures = max($this->iNumMeasures, 1 + max($aMeasures));

            $aSequence = &$this->aMachineSequences[$sMachineName];
            if (null === $oPattern) {
                foreach ($aMeasures as $iPosition) {
                    unset($aSequence[$iPosition]);
                }
            } else {
                if ($oPattern->getNumChannels() > $this->aMachines[$sMachineName]->getNumVoices()) {
                    throw new RangeException('Pattern has too many channels for machine "' . $sMachineName . '"');
                }
                foreach ($aMeasures as $iPosition) {
                    $aSequence[$iPosition] = $oPattern;
                }
            }
        }
        return $this;
    }

    /**
     * Return the Pattern sequence for a named machine. Note that the returned structure is an associatiave array
     * of measure positions to Pattern instance and is unsorted.
     *
     * @param  string                   $sMachineName
     * @return Audio\Sequence\Pattern[]
     * @throws OutOfBoundsException
     */
    public function getSequence(string $sMachineName): array {
        $this->assertMachineExists($sMachineName);
        return $this->aMachineSequences[$sMachineName];
    }



    /**
     * @param  string $sMachineName
     * @throws OutOfBoundsException
     */
    private function assertMachineExists(string $sMachineName): void {
        if (!isset($this->aMachines[$sMachineName])) {
            throw new OutOfBoundsException('Unrecognised machine name "' . $sMachineName . '"');
        }
    }

    /**
     * Add a Pattern. Patterns are assigned to a machine name.
     *
     * @param  string                 $sMachineName
     * @param  Audio\Sequence\Pattern $oPattern
     * @return self
     */
    public function addPattern(string $sMachineName, Audio\Sequence\Pattern $oPattern): self {
        if (isset($this->aMachinePatterns[$sMachineName])) {
            $this->aMachinePatterns[$sMachineName][] = $oPattern;
        } else {
            $this->aMachinePatterns[$sMachineName]   = [$oPattern];
        }
        return $this;
    }

    /**
     * Play the sequence. By default the entire sequence is played, however, the start measure
     * and total number of measures to play can be set.
     *
     * @param Audio\IPCMOutput $oOutput       - Output stream
     * @param float            $fGain         - Default is 1.0
     * @param int              $iStartMeasure - Default is 0, e.g. start at the beginning.
     * @param int              $iNumMeasures  - Default is 0, e.g. play entire sequence
     */
    public function playSequence(
        Audio\IPCMOutput $oOutput,
        float $fGain       = 1.0,
        int $iStartMeasure = 0,
        int $iNumMeasures  = 0,
        int $iSkipLines    = 0
    ): self {

        // Sanity checks
        if ($iStartMeasure < 0 || $iStartMeasure >= $this->iNumMeasures) {
            throw new RangeException('Start measure is out of range');
        }
        if ($iNumMeasures <= 0) {
            $iNumMeasures = $this->iNumMeasures;
        }

        $iLastMeasure    = min($iStartMeasure + $iNumMeasures, $this->iNumMeasures);
        $fBeatsPerSecond = $this->iTempoBeatsPerMinute / 60.0;
        $fLinesPerSecond = $fBeatsPerSecond * $this->iLinesPerBeat;
        $fLinePeriod     = 1.0 / $fLinesPerSecond;
        $fSecondScale    = 1.0 / Audio\IConfig::PROCESS_RATE;

        $oMixer = new Audio\Signal\Operator\FixedMixer($fGain);
        foreach ($this->aMachines as $sMachineName => $oMachine) {
            $oMixer->addInputStream($sMachineName, $oMachine, 1.0);
        }

        // Factor in swing.
        $fSwingPeriod = \M_PI / $fLinePeriod;
        $fSwingDepth  = $this->fSwingDepth * $fLinePeriod / 16; // TODO - the swing measure needs better defining

        $fPlayTime    = microtime(true);
        $fComputeTime = 0.0;
        $iLineOffset  = 0;
        for ($iMeasure = $iStartMeasure; $iMeasure < $iLastMeasure; ++$iMeasure) {
            $aActivePatterns = [];
            //echo "Measure ", $iMeasure, "\n";
            foreach ($this->aMachineSequences as $sMachineName => $aSequence) {
                if (isset($aSequence[$iMeasure])) {
                    $oPattern = $aSequence[$iMeasure];
                    //echo "\t", $sMachineName, ": ", $oPattern->getLabel(), "\n";
                    $aActivePatterns[$sMachineName] = $oPattern;
                } else {
                    //echo "\t", $sMachineName, ": (no pattern)\n";
                }
            }

            $iLastLineNumber = -1;
            $iLineNumber = 0;
            while (true) {
                $iSamplePosition = $oMixer->getPosition();
                $fCurrentTime    = $fSecondScale * $iSamplePosition;

                // Schwing
                $fCurrentTime   += $fSwingDepth * cos($fCurrentTime * $fSwingPeriod);

                $iLineNumber     = (int)floor($fCurrentTime * $fLinesPerSecond) - $iLineOffset + $iSkipLines;

                if ($iLineNumber > $iLastLineNumber) {
                    if ($iLineNumber == $this->iBasePatternLength) {
                        break;
                    }
                    $iLastLineNumber = $iLineNumber;
                    $this->triggerLine($iLineNumber, $aActivePatterns);
                }

                $fStart  = microtime(true);
                $oPacket = $oMixer->emit();
                $fComputeTime += microtime(true) - $fStart;

                $oOutput->write($oPacket);
            }
            $iLineOffset += $this->iBasePatternLength;
        }
        $iSkipLines = 0;
        $fPlayTime = microtime(true) - $fPlayTime;

        printf(
            "Audio Performance %.3f seconds generated in %.3f seconds\n",
            $fPlayTime, $fComputeTime
        );

        return $this;
    }

    /**
     * Trigger the events on the selected line for the active patterns
     *
     * @param Audio\Sequence\Pattern[] $aActivePatterns
     */
    private function triggerLine(int $iLineNumber, array $aActivePatterns): void {
        foreach ($aActivePatterns as $sMachineName => $oPattern) {
            $oMachine = $this->aMachines[$sMachineName];
            $oRow     = $oPattern->getLine($iLineNumber);
            foreach ($oRow as $iChannel => $aEventSets) {
                if (empty($aEventSets)) {
                    continue;
                }

                foreach ($aEventSets as $oEvent) {
                    switch ($oEvent->iType) {
                        case Audio\Sequence\Event::NOTE_ON:
    //                         dprintf("\tLn:%4d Mc:%5s Ch:%2d Ev:NoteOn %s V:%d\n",
    //                             $iLineNumber,
    //                             $sMachineName,
    //                             $iChannel,
    //                             $oEvent->sNote,
    //                             $oEvent->iVelocity
    //                         );

                            $oMachine
                                ->setVoiceNote($iChannel, $oEvent->sNote)
                                ->setVoiceVelocity($iChannel, $oEvent->iVelocity)
                                ->startVoice($iChannel);
                            break;

                        case Audio\Sequence\Event::SET_NOTE:
                            $oMachine
                                ->setVoiceNote($iChannel, $oEvent->sNote);
                            break;

                        case Audio\Sequence\Event::NOTE_OFF:
                            $oMachine
                                ->stopVoice($iChannel);
                            break;

                        case Audio\Sequence\Event::SET_CTRL:
                            $oMachine
                                ->setVoiceControllerValue($iChannel, $oEvent->iController, $oEvent->iValue);
                            break;

                        case Audio\Sequence\Event::MOD_CTRL:
                            $oMachine
                                ->adjustVoiceControllerValue($iChannel, $oEvent->iController, $oEvent->iDelta);
                            break;

                        default:
                            break;
                    }
                }
            }
        }
    }
}
