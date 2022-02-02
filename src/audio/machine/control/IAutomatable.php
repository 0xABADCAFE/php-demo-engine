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

namespace ABadCafe\PDE\Audio\Machine\Control;

/**
 * IAutomatable
 *
 * Main interface for Sequenced controller automation. Events such as SET_CTRL and MOD_CTRL are actioned through this
 * interface.
 */
interface IAutomatable {

    const
        /**
         * Controller types
         */
        CTRL_TYPE_SWITCH = 0, // Controller value accepted as switch/enum/quantized
        CTRL_TYPE_KNOB   = 1, // Controller value is mapped to a continuous value by a Control Curve

        /**
         * Control event input range
         */
        CTRL_MIN_INPUT_VALUE  = 0,    // Smallest value for Event::SET_CTRL
        CTRL_MAX_INPUT_VALUE  = 255,  // Largest value for Event::SET_CTRL
        CTRL_MIN_INPUT_DELTA  = -128, // Largest negative value for Event::MOD_CTRL
        CTRL_MAX_INPUT_DELTA  = 127,  // Largest positive value for Event::MOD_CTRL

        /**
         * Controller number enumeration.
         */
        CTRL_COMMON = 0,    // Controller numbers 0-127 are reserved for universal controllers.
        CTRL_CUSTOM = 128,  // Controller numbers 128-255 are rreserved for machine-specific controllser

        /**
         * Common controllers
         */
        CTRL_VOLUME = 0, // Overall output level, not velocity. Channel is ignored.
        CTRL_PITCH  = 1,

        CTRL_VIBRATO_RATE  = 10,
        CTRL_VIBRATO_DEPTH = 11,
        CTRL_TREMOLO_RATE  = 12,
        CTRL_TREMOLO_DEPTH = 13,

        // Waveform select, per oscillator up to a maximu, of 8 oscillators
        CTRL_OSC_1_WAVE    = 20,
        CTRL_OSC_2_WAVE    = 21,
        CTRL_OSC_3_WAVE    = 22,
        CTRL_OSC_4_WAVE    = 23,
        CTRL_OSC_5_WAVE    = 24,
        CTRL_OSC_6_WAVE    = 25,
        CTRL_OSC_7_WAVE    = 26,
        CTRL_OSC_8_WAVE    = 27,

        // Standard ranges
        CTRL_DEF_LFO_RATE_MIN = 0.125,
        CTRL_DEF_LFO_RATE_MAX = 32.0
    ;

    const CTRL_NAMES = [
        self::CTRL_VOLUME        => 'Volume',
        self::CTRL_PITCH         => 'Pitch Bend',
        self::CTRL_VIBRATO_RATE  => 'Vibrato Rate',
        self::CTRL_VIBRATO_DEPTH => 'Vibrato Depth',
        self::CTRL_TREMOLO_RATE  => 'Tremolo Rate',
        self::CTRL_TREMOLO_DEPTH => 'Tremolo Depth',
        self::CTRL_OSC_1_WAVE    => 'Osc 1 Waveform',
        self::CTRL_OSC_2_WAVE    => 'Osc 2 Waveform',
        self::CTRL_OSC_3_WAVE    => 'Osc 3 Waveform',
        self::CTRL_OSC_4_WAVE    => 'Osc 4 Waveform',
        self::CTRL_OSC_5_WAVE    => 'Osc 5 Waveform',
        self::CTRL_OSC_6_WAVE    => 'Osc 6 Waveform',
        self::CTRL_OSC_7_WAVE    => 'Osc 7 Waveform',
        self::CTRL_OSC_8_WAVE    => 'Osc 8 Waveform',
    ];

    /**
     * Obtain the set of sequence controllable controllers, keyed by Controller Number
     *
     * @return Definition[]
     */
    public function getControllerDefs(): array;

    /**
     * @return array<int, string>
     */
    public function getControllerNames(): array;

    /**
     * Sets a controller to a specific value. Controllers are typically machine specific.
     *
     * @param  int  $iVoiceNumber
     * @param  int  $iController
     * @param  int  $iValue
     * @return self
     */
    public function setVoiceControllerValue(int $iVoiceNumber, int $iController, int $iValue): self;

    /**
     * Modifies a controller value.
     *
     * @param  int  $iVoiceNumber
     * @param  int  $iController
     * @param  int  $iDelta
     * @return self
     */
    public function adjustVoiceControllerValue(int $iVoiceNumber, int $iController, int $iDelta): self;

}

