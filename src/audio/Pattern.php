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

/**
 * Simplie note on event
 */
class NoteOn extends Event {
    public string $sNote      = 'C-3';
    public float  $fIntensity = 0.75;
    public int    $iTicks     = 0;    // forever
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

    public function getNumChannels() : int {
        return $this->iChannels;
    }

    public function getLength() : int {
        return $this->iNumLines;
    }

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


