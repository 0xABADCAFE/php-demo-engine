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
use \SPLFixedArray;

/**
 * RGBASCII
 *
 * ASCII with RGB foreground colour over RGB background.
 *
 * Pixel format is [00][Chr][Fg:R][Fg:G][Fg:B][Bg:R][Bg:G][Bg:B]
 */
class RGBASCIIOverRGB extends BaseAsyncASCIIWithRGB {

    const
        ATTR_TEMPLATE = IANSIControl::ATTR_FG_RGB_TPL . IANSIControl::ATTR_BG_RGB_TPL,
        DATA_FORMAT   = self::DATA_FORMAT_64,
        PIXEL_FORMAT  = self::FORMAT_RGB_ASCII_RGB
    ;

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);
        $this->setRGBWriteMask(0xFFFFFFFFFFFF);
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultPixelValue(): int {
        return $this->iBGColour | $this->iFGColour << 24;
    }

    /**
     * @inheritDoc
     */
    protected function preparePixels(): void {
        $j = 0;
        $oPixels    = $this->getPixels();
        $sRawBuffer = $this->getCharacterBuffer();
        foreach ($oPixels as $i => $iRGBRGB) {
            $j += (int)isset($this->aLineBreaks[$i]);
            $oPixels[$i] = ord($sRawBuffer[$j++]) << 48 | $iRGBRGB;
        }
    }

    /**
     * Draw a frame of pixels to the console. This typically involves decoding a packed array of
     * integer data and converting it into ANSI/ASCII for display.
     *
     * @param string $sData     - The raw binary data representing the pixel array
     * @param string $sInitial  - The first part of the output, e.g. reset the cursor position etc.
     */
    protected function drawFrame(string $sData, string $sInitial): void {
        $aPixels       = (array)unpack(self::DATA_FORMAT_MAP[self::DATA_FORMAT], $sData);
        $sRawBuffer    = $sInitial;
        $iLastForeRGB  = 0xFF000000;
        $iLastBackRGB  = 0xFF000000;
        $i             = 0;
        $iRGBWriteMask = $this->getRGBWriteMask();
        foreach ($aPixels as $iCRGBRGB) {
            $sRawBuffer .= $this->aLineBreaks[$i++] ?? '';
            $iCharCode   = $iCRGBRGB >> 48;
            $iRGBRGB     = $iCRGBRGB & $iRGBWriteMask;
            $iForeRGB    = $iRGBRGB >> 24;
            $iBackRGB    = $iRGBRGB &  0xFFFFFF;
            $sTextChar   = ICustomChars::MAP[$iCharCode] ?? chr($iCharCode);
            $iChanged    = (int)($iForeRGB != $iBackRGB) |
                           (int)($iForeRGB != $iLastForeRGB) << 1 |
                           (int)($iBackRGB != $iLastBackRGB) << 2;
            switch ($iChanged) {
                case 0:
                    // Colours same, unchanged, character not visible
                    $sRawBuffer .= ' ';
                    break;
                case 1:
                    $sRawBuffer .= $sTextChar;
                    break;
                case 2:
                case 4:
                    // Colours same, character not visible
                    $sTextChar = ' ';
                case 5:
                    // Background RGB changes only
                    $sRawBuffer .= sprintf(
                        IANSIControl::ATTR_BG_RGB_TPL,
                        $iBackRGB >> 16,
                        ($iBackRGB >> 8) & 0xFF,
                        ($iBackRGB & 0xFF)
                    ) . $sTextChar;
                    break;
                case 3:
                    // Foreground RGB changes
                    $sRawBuffer .= sprintf(
                        IANSIControl::ATTR_FG_RGB_TPL,
                        $iForeRGB >> 16,
                        ($iForeRGB >> 8) & 0xFF,
                        ($iForeRGB & 0xFF)
                    ) . $sTextChar;
                    break;

                case 6:
                    // Colours same, character not visible
                    $sTextChar = ' ';
                case 7:
                    // Background and foreground changes
                    $sRawBuffer .= sprintf(
                        self::ATTR_TEMPLATE,
                        $iForeRGB >> 16,
                        ($iForeRGB >> 8) & 0xFF,
                        ($iForeRGB & 0xFF),
                        $iBackRGB >> 16,
                        ($iBackRGB >> 8) & 0xFF,
                        ($iBackRGB & 0xFF)
                    ) . $sTextChar;
                    break;
            }
            $iLastForeRGB = $iForeRGB;
            $iLastBackRGB = $iBackRGB;
        }
        // Make sure we output the data in one blast to try to mitigate partial redraw.
        ob_start(null, 0);
        echo $sRawBuffer;
        ob_end_flush();
    }
}
