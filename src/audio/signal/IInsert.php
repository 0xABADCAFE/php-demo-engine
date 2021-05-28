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

/**
 * IInsert
 *
 * Interface for stream insertion classes. These consume a stream and apply some form of insertion effect.
 */
interface IInsert extends IStream {

    /**
     * Obtain the InputStream for this Insert. Returns null if not set yet.
     *
     * @return IStream|null
     */
    public function getInputStream() : ?IStream;

    /**
     * Set the InputStream for this Insert. Until an input stream is set, the insert is disable()d.
     *
     * @param  IStream|null $oInputStream
     * @return self
     */
    public function setInputStream(?IStream $oInputStream) : self;

    /**
     * Get the dry signal level for this Insert.
     *
     * @return float
     */
    public function getDryLevel() : float;

    /**
     * Set the dry signal level for this Insert. Should be in the range 0.0 ... 1.0 but this is not a strict requirement.
     * Values over 1 serve as amplification, values lower than one attenuation. Negative values invert the sign.
     *
     * @param  float $fDryLevel
     * @return self
     */
    public function setDryLevel(float $fDryLevel) : self;
}
