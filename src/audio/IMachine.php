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

/**
 * Interface for machines (synthesis units).
 */
interface IMachine extends Signal\IStream {

    const
        MIN_POLYPHONY = 1,
        MAX_POLYPHONY = 8,

        VOICE_ATTENUATE = 1.0 / self::MAX_POLYPHONY
    ;

    const
        CTRL_PITCH       = 1,
        CTRL_VOLUME      = 2,
        CTRL_CUSTOM      = 128,

        CTRL_MIN_INPUT_VALUE  = 0,
        CTRL_MAX_INPUT_VALUE  = 255,
        CTRL_MIN_INPUT_DELTA  = -128,
        CTRL_MAX_INPUT_DELTA  = 127

    ;

    /**
     * Returns the voice count, i.e. how polyphonic the machine is.
     *
     * @return int
     */
    public function getNumVoices(): int;

    /**
     * Get the level for a specific voice number. Returns zero if the voice number is out of range.
     *
     * @param  int $iVoiceNumber
     * @return float
     */
    public function getVoiceLevel(int $iVoiceNumber): float;

    /**
     * Set the level for a specific voice number. Does not care if the voice number is out of range.
     *
     * @param  int   $iVoiceNumber
     * @param  float $fVolume
     * @return self
     */
    public function setVoiceLevel(int $iVoiceNumber, float $fVolume): self;

    /**
     * Get the overall output level for the machine.
     *
     * @return float
     */
    public function getOutputLevel(): float;

    /**
     * Set the overall output level for the machine.
     *
     * @param  float $fVolume
     * @return self
     */
    public function setOutputLevel(float $fVolume): self;

    /**
     * Start a note on the specified voice. Does nothing if the voice number is out of range.
     *
     * @param  int    $iVoiceNumber
     * @param  string $sNoteName
     * @return self
     */
    public function setVoiceNote(int $iVoiceNumber, string $sNoteName): self;

    /**
     * Set the velocity of the voice. This is in the MIDI range 0 - 127 and can be mapped to various parameters
     * based on Control Curves. Does nothing if the voice number is out of range.
     *
     * @param  int  $iVoiceNumber
     * @param  int  $iVelocity
     * @return self
     */
    public function setVoiceVelocity(int $iVoiceNumber, int $iVelocity): self;


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

    /**
     * Starts the specified voice playing. Does nothing if the voice number is out of range.
     *
     * @param  int    $iVoiceNumber
     * @return self
     */
    public function startVoice(int $iVoiceNumber): self;

    /**
     * Stops the specified voice playing. Does nothing if the voice number is out of range.
     *
     * @param  int    $iVoiceNumber
     * @return self
     */
    public function stopVoice(int $iVoiceNumber): self;

    /**
     * Set an Insert to use on the machine output. This could be a compressor, EQ, etc. Setting null removes any
     * existing insert.
     *
     * @param  Signal\IInsert|null $oInsert
     * @return self
     */
    public function setInsert(?Signal\IInsert $oInsert): self;

    /**
     * Get the currently assigned Insert, if any.
     *
     * @return Signal\IInsert|null
     */
    public function getInsert(): ?Signal\IInsert;
}

