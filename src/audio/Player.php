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

namespace ABadCafe\PDE\Audio;

use ABadCafe\PDE;

/**
 * Player
 */
class Player implements PDE\System\IAsynchronous {

    use PDE\System\TAsynchronous;

    /**
     * Constructor
     */
    public function __construct() {
        $this->initAsyncProcess();
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->closeSocket(self::ID_PARENT);
    }

    /**
     * Start playback
     */
    public function start(): void {

    }

    /**
     * Schtop playback
     */
    public function stop(): void {

    }

    /**
     * @inheritDoc
     */
    protected function runSubprocess(): void {
        $oDrumMachine = new Machine\TRNaN();
        $oDrumMachine->setOutputLevel(0.75);
        $oDrumPattern = new Sequence\Pattern(6, 128);
        $oDrumPattern->addEvent(new Sequence\NoteOn('B4'), 0, 0, 8);
        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 70), 0, 2, 16);
        $oDrumPattern->addEvent(new Sequence\NoteOn('G#4'), 0, 29, 32);

        $oDrumPattern->addEvent(new Sequence\NoteOn('A4'), 1, 4, 8);

        $oDrumPattern->addEvent(new Sequence\NoteOn('B3', 40), 5, 3, 16);
        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 40), 5, 12, 16);

        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 60), 3, 14, 32);

        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 50), 2, 0, 4);
        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 30), 2, 64+1, 4);
        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 50), 2, 2, 4);
        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 30), 2, 64+3, 4);

        $oDrumPattern->addEvent(new Sequence\NoteOn('A4', 50), 4, 6, 32);

        $oDelay = new Signal\Insert\DelayLoop(null);
        $oDelay->setFeedback(0.1);

        $oDrumMachine->setInsert($oDelay);

        // Open the audio
        $oPCMOut = Output\Piped::create();
        $oPCMOut->open();

        $oSequencer = new Machine\Sequencer();
        $oSequencer
            ->setTempo(120)
            ->addMachine('drum', $oDrumMachine)
            ->addPattern('drum', $oDrumPattern)

            ->play($oPCMOut, 128, 4.0)
        ;
    }

}
