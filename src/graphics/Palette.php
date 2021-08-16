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

namespace ABadCafe\PDE\Graphics;
use \SPLFixedArray;

/**
 * Simple Palette class.
 */
class Palette {

    private SPLFixedArray $oEntries;
    private int           $iSize;

    public function __construct(int $iSize) {
        if ($iSize < 2 || $iSize > 256) {
            throw new \LengthException();
        }
        $this->iSize    = $iSize;
        $this->oEntries = SPLFixedArray::fromArray(\array_fill(0, $iSize, 0));
    }

    public function size() : int {
        return $this->iSize;
    }

    /**
     * Accepts a set of indexed RGB values and interpolates a gradient. For example:
     * the array input [ 16 => 0x000000, 47 => 0xFFFFFF ] would create a greyscale
     * gradient over 32 levels spanning palette indexes 16-47. Multiple points can be
     * given.
     *
     * @param int[] $aPoints
     */
    public function gradient(array $aPoints) : SPLFixedArray {
        $iCount = \count($aPoints);
        if ($iCount < 2) {
            throw new \LengthException('A gradient requires at least 2 points');
        }
        \ksort($aPoints);
        $aPositions = \array_keys($aPoints);
        if (\min($aPositions) < 0) {
            throw new OutOfBoundsException('Negative indexes not allowed');
        }
        $aRGBValues = \array_values($aPoints);

        $iLastPosition  = $aPositions[0];
        $iLastRGBValue  = $aRGBValues[0];
        for ($i = 1; $i < $iCount && $iLastPosition < $this->iSize; ++$i) {
            $iPosition  = $aPositions[$i];
            $iRGBValue  = $aRGBValues[$i];

            // Calculate span range interpolant
            $iSpanLen   = $iPosition - $iLastPosition;
            $fSpanFac   = 1.0 / $iSpanLen;

            // Calculate starting R/G/B values
            $iRed       = ($iLastRGBValue >> 16) & 0xFF;
            $iGreen     = ($iLastRGBValue >> 8) & 0xFF;
            $iBlue      = ($iLastRGBValue) & 0xFF;

            // Calculate interpolant steps
            $fRedStep   = $fSpanFac * ((($iRGBValue >> 16) & 0xFF) - $iRed);
            $fGreenStep = $fSpanFac * ((($iRGBValue >> 8) & 0xFF)  - $iGreen);
            $fBlueStep  = $fSpanFac * (($iRGBValue & 0xFF)         - $iBlue);

            // Interpolate
            $j = 0;
            $k = $iLastPosition;
            while ($iSpanLen-- >= 0) {
                $this->oEntries[$k++] =
                    (int)\min(($iRed   + $j * $fRedStep), 255) << 16 |
                    (int)\min(($iGreen + $j * $fGreenStep), 255) << 8 |
                    (int)\min(($iBlue  + $j * $fBlueStep), 255);
                    ++$j;
            }

            $iLastPosition = $iPosition;
            $iLastRGBValue = $iRGBValue;
        }
        return $this->oEntries;
    }

    /**
     * Return the raw palette data.
     */
    public function getEntries() : SPLFixedArray {
        return $this->oEntries;
    }
}
