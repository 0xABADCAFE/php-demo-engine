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
 * Sequence Events. These are unstructured types containing an integer type field optionally followed by other
 * data. Events should be treated as immutable types and the implementation may return the same event object
 * for multiple callers in order to minimise memory usage.
 *
 * Events are created by calling the appropriate static method. Where the event contains additional data, the
 * fields are set identicaly to the call parameters.
 *
 * Since events are basically free form data structures, almost no validation is done on their parameterisation.
 */
class Event {

    const
        NOTHING  = 0,
        NOTE_ON  = 1,
        SET_NOTE = 2,
        NOTE_OFF = 3
    ;

    public int $iType = self::NOTHING;

    /**
     * Singleton note off event
     */
    private static ?self $oNoteOff = null;

    /**
     * @var self[] $aNoteOn - flyweight for note on events
     */
    private static array $aNoteOn  = [];

    /**
     * @var self[] $aSetNote - flyweight for set not events
     */
    private static array $aSetNote = [];

    /**
     * Don't allow arbitary construction of these.
     */
    private function __construct(int $iType) {
        $this->iType = $iType;
    }

    /**
     * Return a note on event.
     *
     * @param  string $sNote
     * @param  int    $iVelocity
     * @return self
     */
    public static function noteOn(string $sNote, int $iVelocity): self {
        $sKey = $sNote . $iVelocity;
        if (!isset(self::$aNoteOn[$sKey])) {
            $oEvent = new self(self::NOTE_ON);
            $oEvent->sNote        = $sNote;
            $oEvent->iVelocity    = $iVelocity;
            self::$aNoteOn[$sKey] = $oEvent;
        }
        return self::$aNoteOn[$sKey];
    }

    /**
     * Return a set note event.
     *
     * @param  string $sNote
     * @return self
     */
    public static function setNote(string $sNote): self {
        if (!isset(self::$aSetNote[$sNote])) {
            $oEvent        = new self(self::SET_NOTE);
            $oEvent->sNote = $sNote;
            self::$aSetNote[$sNote] = $oEvent;
        }
        return self::$aSetNote[$sNote];
    }

    /**
     * Return a note off event.
     *
     * @return self
     */
    public static function noteOff(): self {
        if (null === self::$oNoteOff) {
            self::$oNoteOff = new self(self::NOTE_OFF);
        }
        return self::$oNoteOff;
    }
}
