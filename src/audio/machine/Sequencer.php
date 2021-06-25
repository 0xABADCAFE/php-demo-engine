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

use function ABadCafe\PDE\dprintf;

/**
 * Sequencer
 *
 * Main song sequencer. Defines the tempo, time signature, measure size etc., maintains a set of IMachine
 * instances, the Patterns for them and sequence of those Patterns per Machine. Provided methods are meant to
 * facilitate the construction of a song sequence from an external definition.
 */
class Sequencer {
    const
        MIN_TEMPO_BPM         = 32,
        DEF_TEMPO_BPM         = 120,
        MAX_TEMPO_BPM         = 240,
        MIN_BEATS_PER_MEASURE = 3,
        DEF_BEATS_PER_MEASURE = 4,
        MAX_BEATS_PER_MEASURE = 7,
        MIN_LINES_PER_BEAT    = 1,
        DEF_LINES_PER_BEAT    = 4,
        MAX_LINES_PER_BEAT    = 32,
        DEF_PATTERN_LENGTH    = self::DEF_BEATS_PER_MEASURE * self::DEF_LINES_PER_BEAT
    ;

    private int
        $iTempoBeatsPerMinute = self::DEF_TEMPO_BPM,
        $iBeatsPerMeasure     = self::DEF_BEATS_PER_MEASURE,
        $iLinesPerBeat        = self::DEF_LINES_PER_BEAT,
        $iBasePatternLength   = self::DEF_PATTERN_LENGTH
    ;

    /**
     * @var Audio\IMachine $oMachine[] $aMachines
     */
    private array $aMachines = [];

    /**
     * @var Audio\Sequence\Pattern[][] $aMachinePatterns
     */
    private array $aMachinePatterns = [];
    private array $aMachinePatternLabels;

    /**
     * @var int[][] $aMachineSequences
     */
    private array $aMachineSequences = [];


    /**
     * Set the tempo, in beats per minute.
     *
     * @param  int $iTempoBeatsPerMinute
     * @return self
     */
    public function setTempo(int $iTempoBeatsPerMinute) : self {
        $this->iTempoBeatsPerMinute = min(
            max($iTempoBeatsPerMinute, self::MIN_TEMPO_BPM),
            self::MAX_TEMPO_BPM
        );
        return $this;
    }

    /**
     * Set the expected beats per measure.s
     *
     * @param  int $iBeatsPerMeasure
     * @return self
     */
    public function setBeatsPerMeasure(int $iBeatsPerMeasure) : self {
        $this->iBeatsPerMeasure = min(
            max($iBeatsPerMeasure, self::MIN_BEATS_PER_MEASURE),
            self::MAX_BEATS_PER_MEASURE
        );
        $this->iBasePatternLength = $this->iBeatsPerMeasure * $this->iLinesPerBeat;
        return $this;
    }

    /**
     * Set the lines per beat as applied to any Patterns.
     *
     * @param  int $iLinesPerBeat
     * @return self
     */
    public function setLinesPerBeat(int $iLinesPerBeat) : self {
        $this->iLinesPerBeat = min(
            max($iLinesPerBeat, self::MIN_LINES_PER_BEAT),
            self::MAX_LINES_PER_BEAT
        );
        $this->iBasePatternLength = $this->iBeatsPerMeasure * $this->iLinesPerBeat;
        return $this;
    }


    /**
     * Add a named Machine. Patterns can be added to the same name which will be assigned to that machine.
     *
     * @param  string         $sMachineName
     * @param  Audio\IMachine $oMachine
     * @return self
     */
    public function addMachine(string $sMachineName, Audio\IMachine $oMachine) : self {
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
     * @param  int                    $iMeasures    - How many measures long the pattern should be
     * @param  int[]|null             $aMeasures    - Which measures in the sequence should use the Pattern
     * @return Audio\Sequence\Pattern
     * @throws OutOfBoundsException   - When the machine name is not recognised
     * @throws RangeException         - When the Pattern length is less than 1 measure
     */
    public function createPattern(string $sMachineName, int $iMeasures = 1, ?array $aMeasures = null) : Audio\Sequence\Pattern {
        $this->assertMachineExists($sMachineName);
        if ($iMeasures < 1) {
            throw new RangeException('Pattern length must be at least 1 measure');
        }

        // Create the Pattern.
        $oPattern = new Audio\Sequence\Pattern(
            $this->aMachines[$sMachineName]->getNumVoices(),
            $this->iBasePatternLength * $iMeasures,
            $sMachineName . '_' . $this->aMachinePatternLabels[$sMachineName]++
        );

        // Associate it with the machine in question
        $this->addPattern($sMachineName, $oPattern);

        if (null !== $aMeasures) {
            $this->setSequencePositions($sMachineName, $oPattern, $aMeasures);
        }

        return $oPattern;
    }

    /**
     * Set up a sequence of Patterns for a named machine. The sequence is an array of measure positions
     * where the Pattern will be used. Note that the Pattern can be null, in which case, any patterns
     * in those positions will be cleared. Throws exceptions if the machine name is unrecognised or the
     * Pattern has more channels than the machine has voices. While it is expected that the Pattern will
     * be reused with the machine it was created for, this is not mandatory and can be played
     *
     * @param  string                      $sMachineName - Which machine to insert the patterns for
     * @param  Audio\Sequence\Pattern|null $oPattern     - Pattern to use or null to clear
     * @param  int[]                       $aMeasures    - Which measures in the sequence should use the Pattern
     * @return self
     * @throws OutOfBoundsException        - When the machine name is not recognised
     * @throws RangeException              - When the pattern has more channels than the Machine has voices.
     */
    public function setSequencePositions(
        string $sMachineName,
        ?Audio\Sequence\Pattern $oPattern,
        array $aMeasures
    ) : self {
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
    public function getSequence(string $sMachineName) : array {
        $this->assertMachineExists($sMachineName);
        return $this->aMachineSequences[$sMachineName];
    }

    /**
     * Add a Pattern. Patterns are assigned to a machine name.
     *
     * @param  string                 $sMachineName
     * @param  Audio\Sequence\Pattern $oPattern
     * @return self
     */
    public function addPattern(string $sMachineName, Audio\Sequence\Pattern $oPattern) : self {
        if (isset($this->aMachinePatterns[$sMachineName])) {
            $this->aMachinePatterns[$sMachineName][] = $oPattern;
        } else {
            $this->aMachinePatterns[$sMachineName]   = [$oPattern];
        }
        return $this;
    }

    /**
     * @param  $sMachineName
     * @throws OutOfBoundsException
     */
    private function assertMachineExists(string $sMachineName) {
        if (!isset($this->aMachines[$sMachineName])) {
            throw new OutOfBoundsException('Unrecognised machine name "' . $sMachineName . '"');
        }
    }

    /**
     * PROTOTYPE CODE
     */
    public function play(Audio\IPCMOutput $oOutput, int $iMaxLines = 128, $fGain = 1.0) {
        $fBeatsPerSecond = $this->iTempoBeatsPerMinute / 60.0;
        $fLinesPerSecond = $fBeatsPerSecond * $this->iLinesPerBeat;

//         dprintf(
//             "Starting sequence: %d PBM (%.2f Lines/sec)\n",
//             $this->iTempoBeatsPerMinute,
//             $fLinesPerSecond
//         );
        $oMixer = new Audio\Signal\FixedMixer($fGain);
        foreach ($this->aMachines as $sMachineName => $oMachine) {
            $oMixer->addInputStream($sMachineName, $oMachine, 1.0);
//             dprintf(
//                 "\tAdding stream %s for %s...\n",
//                 $sMachineName,
//                 get_class($oMachine)
//             );
        }
        $fSecondScale = 1.0 / Audio\IConfig::PROCESS_RATE;
        $iLastLineNumber = -1;
        while ($iLastLineNumber < $iMaxLines) {
            $iSamplePosition = $oMixer->getPosition();
            $fCurrentTime    = $fSecondScale * $iSamplePosition;
            $iLineNumber     = (int)floor($fCurrentTime * $fLinesPerSecond);
            if ($iLineNumber !== $iLastLineNumber) {
                $iLastLineNumber = $iLineNumber;
                $this->processLine($iLineNumber);
            }
            $oOutput->write($oMixer->emit());
        }
    }

    /**
     * PROTOTYPE CODE
     */
    private function processLine(int $iLineNumber) {
        $fVelocityScale = 1.0/127.0;
        foreach ($this->aMachines as $sMachineName => $oMachine) {
            $oPattern = $this->aMachinePatterns[$sMachineName][0];
            $iLineNumber %= $oPattern->getLength();
            $oRow = $oPattern->getLine($iLineNumber);
            foreach ($oRow as $iChannel => $oEvent) {
                if ($oEvent instanceof Audio\Sequence\NoteOn) {
//                     dprintf("\tLn:%4d Mc:%5s Ch:%2d Ev:NoteOn %s V:%d\n",
//                         $iLineNumber,
//                         $sMachineName,
//                         $iChannel,
//                         $oEvent->sNote,
//                         $oEvent->iVelocity
//                     );

                    $oMachine
                        ->setVoiceNote($iChannel, $oEvent->sNote)
                        ->setVoiceVelocity($iChannel, $oEvent->iVelocity)
                        ->setVoiceLevel($iChannel, $fVelocityScale * $oEvent->iVelocity)
                        ->startVoice($iChannel);
                } else if ($oEvent instanceof Audio\Sequence\SetNote) {
                    $oMachine
                        ->setVoiceNote($iChannel, $oEvent->sNote);
                } else if ($oEvent instanceof Audio\Sequence\NoteOff) {
                    $oMachine
                        ->stopVoice($iChannel);
                }
            }
        }
    }


}
