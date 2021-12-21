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

namespace ABadCafe\PDE\Display;
use ABadCafe\PDE;

use function \sprintf, \str_repeat, \strlen, \min, \explode, \count, \array_slice, \strpos, \substr;

/**
 * TASCIIArt
 *
 * A simple mixin for use with the IASCIIArt interface
 */
trait TASCIIArt {

    private int $iMaxLuma  = IASCIIArt::DEF_MAX_LUMA;

    protected int
        $iFGColour = IASCIIArt::DEF_FG_COLOUR,
        $iBGColour = IASCIIArt::DEF_BG_COLOUR
    ;

    private string
        $sRawBuffer,
        $sNewRawBuffer,
        $sLumaChars = IASCIIArt::DEF_LUMA_CHAR,
        $sFGColour  = '',
        $sBGColour  = ''
    ;

    /**
     * Set the default foreground ANSI colour to use.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setForegroundColour(int $iColour): self {
        $iColour &= 0xFF;
        if ($iColour < 16) {
            $iColour = IASCIIArt::REMAP_DEFAULTS[$iColour & 0x0F];
        }
        if ($iColour != $this->iFGColour) {
            $this->iFGColour = $iColour;
            $this->sFGColour = sprintf(
                IANSIControl::ATTR_FG_FIXED_TPL,
                $this->iFGColour
            );
        }
        return $this;
    }

    /**
     * Set the default background ANSI colour to use.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setBackgroundColour(int $iColour): self {
        $iColour &= 0xFF;
        if ($iColour < 16) {
            $iColour = IASCIIArt::REMAP_DEFAULTS[$iColour & 0x0F];
        }
        if ($iColour != $this->iBGColour) {
            $this->iBGColour = $iColour;
            $this->sBGColour = sprintf(
                IANSIControl::ATTR_BG_FIXED_TPL,
                $this->iBGColour
            );
        }
        return $this;
    }

    /**
     * Initialise the default fixed colours.
     */
    private function initFixedColours(): void {
        $this->sBGColour = sprintf(
            IANSIControl::ATTR_BG_FIXED_TPL,
            $this->iBGColour
        );
        $this->sFGColour = sprintf(
            IANSIControl::ATTR_FG_FIXED_TPL,
            $this->iFGColour
        );
    }

    /**
     * Constructor hook
     *
     * @param int $iWidth
     * @param int $iHeight
     */
    private function initASCIIBuffer(int $iWidth, int $iHeight): void {
        $this->sRawBuffer    = // drop through
        $this->sNewRawBuffer = str_repeat(str_repeat(' ', $iWidth) . "\n", $iHeight);
    }

    /**
     * @inheritDoc
     */
    public function getCharacterWidth(): int {
        return $this->iWidth + 1; // 1 for the newline
    }

    /**
     * @inheritDoc
     */
    private function resetASCIIBuffer(): void {
        $this->sRawBuffer = $this->sNewRawBuffer;
    }

    /**
     * @inheritDoc
     */
    public function &getCharacterBuffer(): string {
        return $this->sRawBuffer;
    }

    /**
     * @inheritDoc
     */
    public function getLuminanceCharacters(): string {
        return $this->sLumaChars;
    }

    /**
     * @inheritDoc
     */
    public function getMaxLuminance(): int {
        return $this->iMaxLuma;
    }

    /**
     * @inheritDoc
     */
    public function setLuminanceCharacters(string $sLumaChars): self {
        if (empty($sLumaChars)) {
            $this->sLumaChars = IASCIIArt::DEF_LUMA_CHAR;
            $this->iMaxLuma   = IASCIIArt::DEF_MAX_LUMA;
        } else {
            $iLength = strlen($sLumaChars);
            if ($iLength < 2) {
                throw new \LengthException();
            }
            $this->sLumaChars = $sLumaChars;
            $this->iMaxLuma   = $iLength - 1;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeTextBounded(string $sText, int $iX, int $iY, int $iMaxX = 0, $iMaxY = 0): self {
        if (
            empty($sText)         || // nothing to render
            $iX >= $this->iWidth  || // completely off right
            $iY >= $this->iHeight    // completely off bottom
        ) {
            return $this;
        }

        // Determine boundary
        $iMaxX = ($iMaxX < 1) ?
            $this->iWidth :
            min($iMaxX, $this->iWidth);
        $iMaxY = ($iMaxY < 1) ?
            $this->iHeight :
            min($iMaxY, $this->iHeight);

        if ($iX > $iMaxX || $iY > $iMaxY) {
            return $this;
        }

        $aStrings = explode("\n", $sText);

        if (count($aStrings) + $iY < 0) {
            return $this; // Completely off the top
        }

        // Deal with negative Y coordinate
        if ($iY < 0) {
            $aStrings    = array_slice($aStrings, -$iY);
            $iY          = 0;
        }

        foreach ($aStrings as $sString) {
            if ($iY > $iMaxY) {
                break;
            }
            $this->writeRightClippedSpan($sString, $iX, $iY++, $iMaxX);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function writeTextSpan(string $sText, int $iX, int $iY, int $iMaxX = 0): self {

        $iMaxX = ($iMaxX < 1) ?
            $this->iWidth :
            min($iMaxX, $this->iWidth);

        // Sanity check
        if (
            $iY <  0              ||
            $iY >= $this->iHeight ||
            $iX >  $iMaxX         ||
            empty($sText)
        ) {
            return $this;
        }
        // Restrict to 1 line
        $iEnd  = strpos($sText, "\n");
        $sText = ($iEnd === false) ? $sText : substr($sText, 0, $iEnd);
        if (empty($sText)) {
            return $this;
        }
        $this->writeRightClippedSpan($sText, $iX, $iY, $iMaxX);
        return $this;
    }

    /**
     * Writes a right clipped span of text. Assumes $iY is in range.
     *
     * @param string $sText
     * @param int    $iX
     * @param int    $iY
     * @param int    $iMaxX
     */
    private function writeRightClippedSpan(string $sText, int $iX, int $iY, $iMaxX): void {

        // Handle negative X by chopping off the left
        if ($iX < 0) {
            $sText = substr($sText, -$iX);
            $iX    = 0;
        }
        if (empty($sText)) {
            return;
        }

        $iLength = strlen($sText);
        if ($iX + $iLength > $iMaxX) {
            $iLength = $iMaxX - $iX;
        }

        $iDstIndex = $iY * $this->getCharacterWidth() + $iX;
        $iSrcIndex = 0;
        while ($iLength--) {
            $this->sRawBuffer[$iDstIndex++] = $sText[$iSrcIndex++];
        }
    }
}
