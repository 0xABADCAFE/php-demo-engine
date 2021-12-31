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

/**
 * ISequenceControllable
 *
 * Main interface for Sequenced controller automation. Events such as SET_CTRL and MOD_CTRL are actioned through this
 * interface.
 */
interface ISequenceControllable {

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

        CTRL_PITCH  = 1,
        CTRL_VOLUME = 2
    ;

    /**
     * Obtain the set of sequence controllable controllers, keyed by Controller Number
     *
     * @return array<int, \stdClass>
     */
    public function getControllerDefs(): array;


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

