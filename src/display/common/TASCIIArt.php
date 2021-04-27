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

/**
 * TASCIIArt
 *
 * A simple mixin for use with the IASCIIArt interface
 */
trait TASCIIArt {

    private int
        $iMaxLuma  = IASCIIArt::DEF_MAX_LUMA,
        $iFGColour = IASCIIArt::DEF_FG_COLOUR,
        $iBGColour = IASCIIArt::DEF_BG_COLOUR
    ;

    private string
        $sRawBuffer,
        $sNewRawBuffer,
        $sLumaChars = IASCIIArt::DEF_LUMA_CHAR,
        $sSetColour = '';

    /**
     * Set the default foreground ANSI colour to use.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setForegroundColour(int $iColour) : self {
        $iColour &= 7;
        if ($iColour != $this->iFGColour) {
            $this->iFGColour  = $iColour;
            $this->sSetColour = sprintf(
                IANSIControl::ATTR_COLOUR_TPL,
                $this->iFGColour,
                $this->iBGColour
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
    public function setBackgroundColour(int $iColour) : self {
        $iColour &= 7;
        if ($iColour != $this->iFGColour) {
            $this->iBGColour  = $iColour;
            $this->sSetColour = sprintf(
                IANSIControl::ATTR_COLOUR_TPL,
                $this->iFGColour,
                $this->iBGColour
            );
        }
        return $this;
    }

    /**
     * Constructor hook
     *
     * @param int $iWidth
     * @param int $iHeight
     */
    private function initASCIIBuffer(int $iWidth, int $iHeight) {
        $this->sRawBuffer    = // drop through
        $this->sNewRawBuffer = str_repeat(str_repeat(' ', $iWidth) . "\n", $iHeight);
    }

    /**
     * @inheritDoc
     */
    public function getCharacterWidth() : int {
        return $this->iWidth + 1; // 1 for the newline
    }

    /**
     * @inheritDoc
     */
    private function resetASCIIBuffer() {
        $this->sRawBuffer = $this->sNewRawBuffer;
    }

    /**
     * @inheritDoc
     */
    public function &getCharacterBuffer() : string {
        return $this->sRawBuffer;
    }

    /**
     * @inheritDoc
     */
    public function getLuminanceCharacters() : string {
        return $this->sLumaChars;
    }

    /**
     * @inheritDoc
     */
    public function getMaxLuminance() : int {
        return $this->iMaxLuma;
    }

    /**
     * @inheritDoc
     */
    public function setLuminanceCharacters(string $sLumaChars) : self {
        $iLength = strlen($sLumaChars);
        if ($iLength < 2) {
            throw new \LengthException();
        }
        $this->sLumaChars = $sLumaChars;
        $this->iMaxLuma   = $iLength - 1;
        return $this;
    }
}
