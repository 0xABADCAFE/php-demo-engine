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
        NOTE_OFF = 3,
        SET_CTRL = 4,
        MOD_CTRL = 5
    ;

    public int $iType = self::NOTHING;

    public ?string $sNote;
    public ?int    $iVelocity;

    /**
     * Singleton note off event
     */
    private static ?self $oNoteOff = null;

    /**
     * @var array<string, self> $aNoteOn - flyweight for note on events, keyed by note/velocity
     */
    private static array $aNoteOn  = [];

    /**
     * @var self[] $aSetNote - flyweight for set not events, keyed by note
     */
    private static array $aSetNote = [];

    /**
     * @var array<string, self> $aSetCtrl - flyweight for controller set events, keyed by ctrl/value
     */
    private static array $aSetCtrl = [];

    /**
     * @var array<string, self> $aModCtrl - flyweight for controller modify events, keyed by ctrl/delta
     */
    private static array $aModCtrl = [];


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

    /**
     * Return a set controller event
     *
     * @param  int $iController
     * @param  int $iValue
     * @return self
     */
    public static function setCtrl(int $iController, int $iValue): self {
        $sKey = $iController . ':' . $iValue;
        if (!isset(self::$aSetCtrl[$sKey])) {
            $oEvent = new self(self::SET_CTRL);
            $oEvent->iController   = $iController;
            $oEvent->iValue        = $iValue;
            self::$aSetCtrl[$sKey] = $oEvent;
        }
        return self::$aSetCtrl[$sKey];
    }

    /**
     * Return a modify controller event
     *
     * @param  int $iController
     * @param  int $iDelta
     * @return self
     */
    public static function modCtrl(int $iController, int $iDelta): self {
        $sKey = $iController . ':' . $iDelta;
        if (!isset(self::$aModCtrl[$sKey])) {
            $oEvent = new self(self::MOD_CTRL);
            $oEvent->iController   = $iController;
            $oEvent->iDelta        = $iDelta;
            self::$aModCtrl[$sKey] = $oEvent;
        }
        return self::$aModCtrl[$sKey];
    }

}
