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

namespace ABadCafe\PDE\Util;

/**
 * Vec3D
 *
 * Basic 3 component Vector. Implements a fluent interface in which the current instance accumulates changes. At
 * any point in a fluent chain, you can break out using clone() where a copy of the current instance is returned.
 *
 * $oVec = new Vec3(5.0, -1.25, 15.0)
 *     ->cross($oSomeOtherVec)
 *     ->normalise();
 *
 * Common code in these methods is intentionally left unrefactored to improve performance in PHP8/JIT.
 */
final class Vec3D {

    public float $fX, $fY, $fZ;

    /**
     * Constructor
     *
     * @param float $fX
     * @param float $fY
     * @param float $fZ
     */
    public function __construct(float $fX = 0.0, $fY = 0.0, $fZ = 0.0) {
        $this->fX = $fX;
        $this->fY = $fY;
        $this->fZ = $fZ;
    }

    /**
     * Returns a copy of the current instance.
     *
     * @return self
     */
    public function clone() : self {
        return clone $this;
    }

    /**
     * Reverses the current instance's direction. This is semantically the same as scaling by -1, but we can
     * realise this directly.
     *
     * @return self
     */
    public function reverse() : self {
        $this->fX = -$this->fX;
        $this->fY = -$this->fY;
        $this->fZ = -$this->fZ;
    }

    /**
     * Scales the current instance by a scalar value.
     *
     * @param  float $fScale
     * @return self
     */
    public function scale(float $fScale) : self {
        $this->fX *= $fScale;
        $this->fY *= $fScale;
        $this->fZ *= $fScale;
        return $this;
    }

    /**
     * Scales the current instance's x, y and z components using another instance's components.
     *
     * @param  self $oVec
     * @return self
     */
    public function scaleVec(self $oVec) : self {
        $this->fX *= $oVec->fX;
        $this->fY *= $oVec->fY;
        $this->fZ *= $oVec->fZ;
        return $this;
    }

    /**
     * Adds another vector instance to this one.
     *
     * @param  self $oVec
     * @return self
     */
    public function add(self $oVec) : self {
        $this->fX += $oVec->fX;
        $this->fY += $oVec->fY;
        $this->fZ += $oVec->fZ;
        return $this;
    }

    /**
     * Subtracts another vector instance from this one.
     *
     * @param  self $oVec
     * @return self
     */
    public function sub(self $oVec) : self {
        $this->fX -= $oVec->fX;
        $this->fY -= $oVec->fY;
        $this->fZ -= $oVec->fZ;
        return $this;
    }

    /**
     * Calculates the cross product of the current instance with another, overwriting this instance.
     *
     * @param  self $oVec
     * @return self
     */
    public function cross(self $oVec) : self {
        $fX = $this->fY * $oVec->fZ - $this->fZ * $oVec->fY;
        $fY = $this->fZ * $oVec->fX - $this->fX * $oVec->fZ;
        $fZ = $this->fX * $oVec->fY - $this->fY * $oVec->fX;
        $this->fX = $fX;
        $this->fY = $fY;
        $this->fZ = $fZ;
        return $this;
    }

    /**
     * Calculates the dot product of the current instance with another.
     *
     * @param  self $oVec
     * @return float
     */
    public function dot(self $oVec) : float {
        return $this->fX * $oVec->fX + $this->fY * $oVec->fY + $this->fZ * $oVec->fZ;
    }

    /**
     * Calculates the scalar magnitude of the current instance.
     */
    public function magnitude() : float {
        return sqrt(
            $this->fX * $this->fX +
            $this->fY * $this->fY +
            $this->fZ * $this->fZ
        );
    }

    /**
     * Normalises the current instance.
     *
     * @return self
     */
    public function normalise() : self {
        $fInvMag = 1.0/sqrt(
            $this->fX * $this->fX +
            $this->fY * $this->fY +
            $this->fZ * $this->fZ
        );
        $this->fX *= $fInvMag;
        $this->fY *= $fInvMag;
        $this->fZ *= $fInvMag;
        return $this;
    }
}
