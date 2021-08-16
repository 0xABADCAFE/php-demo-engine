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

/**
 * Pattern
 *
 * Basic pattern block. Implements a sparse representation of the event data using regular PHP arrays. There is one
 * array per channel that is indexed by line number. Only line numbers that have events have an entry.
 *
 * Requested lines are returned as fixed length SPLFixedArray representations by combining the events (if any) in each
 * of the channel arrays for that line number.
 */
class Pattern {

    private int
        $iNumChannels   = 1,
        $iNumLines      = 64
    ;

    private string $sLabel = '';

    private array $aChannels;

    private SPLFixedArray $oRow;

    /**
     * Constructor. Expects a number of channels and lines.
     *
     * @param int $iNumChannels
     * @param int $iNumLines
     */
    public function __construct(int $iNumChannels, int $iNumLines, string $sLabel = '') {
        $this->iNumChannels = \max(1, $iNumChannels);
        $this->iNumLines    = \max(1, $iNumLines);

        $this->oRow         = new SPLFixedArray($this->iNumChannels);
        $this->aChannels    = \array_fill(0, $this->iNumChannels, []);
        $this->sLabel       = $sLabel;
    }

    /**
     * Number of channels in this pattern,
     *
     * @return int
     */
    public function getNumChannels() : int {
        return $this->iNumChannels;
    }

    /**
     * Length of the pattern in lines
     *
     * @return int
     */
    public function getLength() : int {
        return $this->iNumLines;
    }

    public function getLabel() : string {
        return $this->sLabel;
    }

    /**
     * Return the enumerated line
     *
     * @param  int $iLineNumber
     * @return SPLFixedArray
     */
    public function getLine(int $iLineNumber) : SPLFixedArray {
        $oRow = clone $this->oRow;
        foreach ($this->aChannels as $i => &$aChannelEvents) {
            $oRow[$i] = $aChannelEvents[$iLineNumber] ?? null;
        }
        return $oRow;
    }

    /**
     * Fluently add an event to the pattern at the specified channel and line number. This can also be set to repeat.
     *
     * @param  Event $oEvent
     * @param  int   $iChannel    - Which channel to add the event to
     * @param  int   $iLineNumber - Which line number to add the event on
     * @param  int   $iEvery      - Repeat every N lines (0 for no repeat)
     * @param  int   $iUntil      - Repeat until line ... (0 for end of block)
     * @return self
     * @throws \OutOfBoundsException
     */
    public function addEvent(Event $oEvent, int $iChannel, int $iLineNumber, int $iEvery = 0, $iUntil = 0) : self {
        if (
            $iChannel < 0    || $iChannel    >= $this->iNumChannels ||
            $iLineNumber < 0 || $iLineNumber >= $this->iNumLines
        ) {
            throw new \OutOfBoundsException();
        }

        $aChannelEvents = &$this->aChannels[$iChannel];
        if ($iEvery > 0) {
            if ($iUntil <= 0 || $iUntil > $this->iNumLines) {
                $iUntil = $this->iNumLines;
            }
            while ($iLineNumber < $iUntil) {
                $aChannelEvents[$iLineNumber] = $oEvent;
                $iLineNumber += $iEvery;
            }
        } else {
            $aChannelEvents[$iLineNumber] = $oEvent;
        }
        return $this;
    }
}
