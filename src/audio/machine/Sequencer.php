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

use function ABadCafe\PDE\dprintf;

/**
 * Sequencer
 */
class Sequencer {
    const
        MIN_TEMPO_BPM      = 32,
        DEF_TEMPO_BPM      = 120,
        MAX_TEMPO_BPM      = 240,
        MIN_LINES_PER_BEAT = 1,
        DEF_LINES_PER_BEAT = 4,
        MAX_LINES_PER_BEAT = 32
    ;

    private int
        $iBPM = self::DEF_TEMPO_BPM,
        $iLPB = self::DEF_LINES_PER_BEAT
    ;

    /**
     * @var Audio\IMachine $oMachine[] $aMachines
     */
    private array $aMachines = [];

    /**
     * @var Audio\Sequence\Pattern[] $aPatterns
     */
    private array $aPatterns = [];

    /**
     * Set the tempo, in beats per minute.
     *
     * @param  int $iBPM
     * @return self
     */
    public function setTempo(int $iBPM) : self {
        $this->iBPM = min(max($iBPM, self::MIN_TEMPO_BPM), self::MAX_TEMPO_BPM);
        return $this;
    }

    /**
     * Set the lines per beat as applied to any Patterns.
     *
     * @param  int $iLPB
     * @return self
     */
    public function setLinesPerBeat(int $iLPB) : self {
        $this->iLPB = min(max($iLPB, self::MIN_LINES_PER_BEAT), self::MAX_LINES_PER_BEAT);
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
        $this->aMachines[$sMachineName] = $oMachine;
        return $this;
    }

    /**
     * Add a Pattern. Patterns are assigned to a machine name.
     *
     * @param  string                 $sMachineName
     * @param  Audio\Sequence\Pattern $oPattern
     * @return self
     */
    public function addPattern(string $sMachineName, Audio\Sequence\Pattern $oPattern) : self {
        if (isset($this->aPatterns[$sMachineName])) {
            $this->aPatterns[$sMachineName][] = $oPattern;
        } else {
            $this->aPatterns[$sMachineName]   = [$oPattern];
        }
        return $this;
    }

    /**
     * PROTOTYPE CODE
     */
    public function play(Audio\IPCMOutput $oOutput, int $iMaxLines = 128) {
        $fBeatsPerSecond = $this->iBPM / 60.0;
        $fLinesPerSecond = $fBeatsPerSecond * $this->iLPB;

        dprintf(
            "Starting sequence: %d PBM (%.2f Lines/sec)\n",
            $this->iBPM,
            $fLinesPerSecond
        );

        $oMixer = new Audio\Signal\FixedMixer();
        foreach ($this->aMachines as $sMachineName => $oMachine) {
            $oMixer->addInputStream($sMachineName, $oMachine, 1.0);
            dprintf(
                "\tAdding stream %s for %s...\n",
                $sMachineName,
                get_class($oMachine)
            );
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
        foreach ($this->aMachines as $sMachineName => $oMachine) {
            $oPattern = $this->aPatterns[$sMachineName][0];
            $iLineNumber %= $oPattern->getLength();
            $oRow = $oPattern->getLine($iLineNumber);
            foreach ($oRow as $iChannel => $oEvent) {
                if ($oEvent instanceof Audio\Sequence\NoteOn) {
                    dprintf("\tLn:%4d Mc:%5s Ch:%2d Ev:NoteOn %s                 \r",
                        $iLineNumber,
                        $sMachineName,
                        $iChannel,
                        $oEvent->sNote
                    );

                    $oMachine
                        ->setVoiceNote($iChannel, $oEvent->sNote)
                        ->startVoice($iChannel);
                }
            }
        }
    }
}
