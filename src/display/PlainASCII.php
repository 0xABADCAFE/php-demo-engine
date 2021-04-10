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
 * PlainASCII
 *
 * Absolutely basic string buffer for ASCII art only.
 */
class PlainASCII implements PDE\IDisplay, IASCIIArt {

    private int     $iWidth, $iHeight, $iMaxLuma = self::DEF_MAX_LUMA;
    private string  $sRawBuffer, $sNewRawBuffer, $sLumaChars = self::DEF_LUMA_CHAR;

    private static array $aBlockMapSearch = [], $aBlockMapReplace = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        if ($iWidth < self::I_MIN_WIDTH || $iHeight < self::I_MIN_HEIGHT) {
            throw new \RangeException('Invalid dimensions');
        }
        $this->iWidth        = $iWidth;
        $this->iHeight       = $iHeight;
        $this->sRawBuffer    = // drop through
        $this->sNewRawBuffer = str_repeat(str_repeat(' ', $iWidth) . "\n", $iHeight);

        if (empty(self::$aBlockMapSearch)) {
            self::$aBlockMapSearch  = array_map('chr', array_keys(ICustomChars::MAP));
            self::$aBlockMapReplace = array_values(ICustomChars::MAP);

        }

        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::CRSR_ON, "\n";
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        printf(IANSIControl::TERM_SIZE_TPL, $this->iHeight + 2, $this->iWidth + 1);
        $this->clear();
        echo IANSIControl::TERM_CLEAR . IANSIControl::CRSR_OFF;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getWidth() : int {
        return $this->iWidth;
    }

    /**
     * @inheritDoc
     */
    public function getSpanWidth() : int {
        return $this->iWidth + 1; // 1 for the newline
    }

    /**
     * @inheritDoc
     */
    public function getHeight() : int {
        return $this->iHeight;
    }

    /**
     * @inheritDoc
     */
    public function clear() : self {
        $this->sRawBuffer = $this->sNewRawBuffer;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        echo IANSIControl::CRSR_TOP_LEFT .
            str_replace(
                self::$aBlockMapSearch,
                self::$aBlockMapReplace,
                $this->sRawBuffer
            );
        return $this;
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
