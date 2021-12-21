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

namespace ABadCafe\PDE\Graphics\BlitterModes;
use ABadCafe\PDE\Graphics\IPixelBuffer;

/**
 * Realisation of various blitter operations.
 */
interface IMode {

    /**
     * Solid fill routine.
     *
     * @param IPixelBuffer $oTarget
     * @param int $iValue
     * @param int $iTargetX
     * @param int $iTargetY
     * @param int $iWidth
     * @param int $iHeight
     */
    public function fill(
        IPixelBuffer $oTarget,
        int $iValue,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ): void;

    /**
     * Main copy operation. The rectagular region defined by {iSourceX, iSourceY, iWidth, iHeight} is copied
     * from the source IPixelBuffer and written to the target IPixelBuffer at position {iTargetX, iTargetY}.
     *
     * The area to be transferred must be strictly within the limits of both the source and target. No checks
     * are performed and the operation will fail.
     *
     * @param IPixelBuffer $oSource
     * @param IPixelBuffer $oTarget
     * @param int $iSourceX
     * @param int $iSourceY
     * @param int $iTargetX
     * @param int $iTargetY
     * @param int $iWidth
     * @param int $iHeight
     */
    public function copy(
        IPixelBuffer $oSource,
        IPixelBuffer $oTarget,
        int $iSourceX,
        int $iSourceY,
        int $iTargetX,
        int $iTargetY,
        int $iWidth,
        int $iHeight
    ): void;
}
