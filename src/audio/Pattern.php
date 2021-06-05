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

namespace ABadCafe\PDE\Audio\Sequence;

use \SPLFixedArray;

class Event {

}

class SetNote extends Event {
    public string $sNote;
    public function __construct(string $sNote) {
        $this->sNote = $sNote;
    }
}


/**
 * Simple note on event
 */
class NoteOn extends Event {
    public string $sNote;
    public int    $iVelocity;

    public function __construct(string $sNote, int $iVelocity = 100) {
        $this->sNote     = $sNote;
        $this->iVelocity = $iVelocity;
    }
}

class NoteOff extends Event {

}


/**
 * Basic Pattern block
 */
class Pattern {

    private int
        $iNumChannels   = 1,
        $iNumLines      = 64
    ;

    private array $aEvents = [];

    public function __construct(int $iNumChannels, int $iNumLines) {
        $this->iNumChannels = max(1, $iNumChannels);
        $this->iNumLines    = max(1, $iNumLines);
        $oRow = new SPLFixedArray($this->iNumChannels);
        $this->oRows = new SPLFixedArray($this->iNumLines);
        for ($i = 0; $i < $this->iNumLines; ++$i) {
            $this->oRows[$i] = clone $oRow;
        }
    }

    /**
     * Number of channels in this pattern
     */
    public function getNumChannels() : int {
        return $this->iChannels;
    }

    /**
     * Length of the pattern in lines
     */
    public function getLength() : int {
        return $this->iNumLines;
    }

    /**
     * Return the enumerated line
     */
    public function getLine(int $iLineNumber) : SPLFixedArray {
        return $this->oRows[$iLineNumber];
    }

    public function addEvent(Event $oEvent, int $iChannel, int $iLineNumber, int $iEvery = 0) {
        if ($iEvery > 0) {
            while ($iLineNumber < $this->iNumLines) {
                $this->oRows[$iLineNumber][$iChannel] = $oEvent;
                $iLineNumber += $iEvery;
            }
        } else {
            $this->oRows[$iLineNumber][$iChannel] = $oEvent;
        }
    }
}


