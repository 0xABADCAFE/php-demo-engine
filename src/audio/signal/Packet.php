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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;
use \SPLFixedArray;

/**
 * Signal Packet. A fixed length array of floating point values.
 *
 * Based on the GIMPS implementation, simplified.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 */
class Packet extends \SPLFixedArray {

    private static int $iNextIndex = 0;

    /**
     * Simple index counter to be used by TPacketIndexAware implementations.
     */
    public static function getNextIndex() : int {
        return ++self::$iNextIndex;
    }

    /**
     * A one-time created prototype instance that we clone to get new ones.
     */
    private static self $oEmpty;

    /**
     * The constructor is only here as we are overriding to a fixed length. New instances should only be created
     * using the create() method which clones the default empty instance.
     */
    public function __construct() {
        parent::__construct(Audio\IConfig::PACKET_SIZE);
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] = 0.0;
        }
    }

    /**
     * Ensure the empty instance is created.
     */
    public static function init() {
        self::$oEmpty = new self();
    }

    /**
     * Obtain a new instance. This is a shallow copy of the prototype and very fast.
     *
     * @return self
     */
    public static function create() : self {
        return clone self::$oEmpty;
    }

    /**
     * Fill the packet with a given value.
     *
     * @param  float $fValue
     * @return self
     */
    public function fillWith(float $fValue) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] = $fValue;
        }
        return $this;
    }

    /**
     * Scale the packet by a given factor.
     *
     * @param  float $fValue
     * @return self
     */
    public function scaleBy(float $fValue) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] *= $fValue;
        }
        return $this;
    }

    /**
     * Bias the packet by a given offset.
     *
     * @param  float $fValue
     * @return self
     */
    public function biasBy(float $fValue) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] += $fValue;
        }
        return $this;
    }

    /**
     * Scale and Bias the packet.
     *
     * @param  float $fScale
     * @param  float $fBias
     * @return self
     */
    public function scaleAndBiasBy(float $fScale, float $fBias) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] = ($this[$i] * $fScale) + $fBias;
        }
        return $this;
    }

    /**
     * Directly sum the values of another packet
     *
     * @param  self $oPacket
     * @return self
     */
    public function sumWith(self $oPacket) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] += $oPacket[$i];
        }
        return $this;
    }

    /**
     * Directly modulate by the values of another packet
     *
     * @param  self $oPacket
     * @return self
     */
    public function modulateWith(self $oPacket) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] *= $oPacket[$i];
        }
        return $this;
    }

    /**
     * Accumulate scaled values from another packet.
     *
     * @param  self  $oPacket
     * @param  float $fValue
     * @return self
     */
    public function accumulate(self $oPacket, float $fValue) : self {
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $this[$i] += $oPacket[$i] * $fValue;
        }
        return $this;
    }
}

/**
 * TPacketIndexAware
 *
 * Utility trait for Packet generators to determine whether or not they need to calculate a new Packet or not.
 */
trait TPacketIndexAware {
    protected int $iLastIndex = 0;

    /**
     * Checks to see whether or not we can use the last calculated data for a given input index.
     *
     * If null is provided, we ask the Packet for the next index, assign it and return false. Otherwise
     * if the index provided is different than the index we last saw, we update it and return false.
     * Finally if the index provided is the same as the last index we saw, we return true as this case
     * indicates we've been asked for the most recent data more than once.
     *
     * @param  int|null $iIndex
     * @return bool
     */
    protected function useLast(?int $iIndex) : bool {
        if (null === $iIndex) {
            $this->iLastIndex = Packet::getNextIndex();
            return false;
        } else if ($this->iLastIndex !== $iIndex) {
            $this->iLastIndex = $iIndex;
            return false;
        }
        return true;
    }
}

/**
 * Eat it, PSR. Nobody cares here.
 */
Packet::init();
