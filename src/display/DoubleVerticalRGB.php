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
 * DoubleVerticalRGB
 *
 */
class DoubleVerticalRGB extends Base implements IPixelled  {

    use TPixelled, TInstrumented, TAsynchronous;

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        // Height must be even
        $iHeight &= ~1;
        parent::__construct($iWidth, $iHeight);
        ini_set('output_buffering', 'true');

        // Initialise the subprocess now as it only needs access to the properties evaluated to now.
        $this->initAsyncProcess();
        $this->initPixelBuffer($iWidth, $iHeight, self::PIX_ASCII_RGB2);
        $this->reset();
    }

    /**
     * Destructor. Ensured our end of the socket pair is closed.
     */
    public function __destruct() {
        $this->closeSocket(1);
        echo IANSIControl::CRSR_ON, "\n";
        $this->reportRedraw();
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        printf(IANSIControl::TERM_SIZE_TPL, ($this->iHeight >> 1) + 2, $this->iWidth + 1);
        $this->clear();
        echo IANSIControl::TERM_CLEAR . IANSIControl::CRSR_OFF;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function clear() : self {
        $this->resetPixelBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $this->beginRedraw();
        $this->sendPixels($this->oPixels);
        $this->endRedraw();
        return $this;
    }

    /**
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    private function subprocessRenderLoop() {
        $sInput  = '';
        $iExpectSize = $this->iWidth * $this->iHeight * 4;
        $sTemplate   = IANSIControl::ATTR_BG_RGB_TPL;
        $iShortReads = 0;

        $aTemplates = [
            // Everything changed
            0 => IANSIControl::ATTR_FG_RGB_TPL . IANSIControl::ATTR_BG_RGB_TPL . ICustomChars::MAP[0x80],

            // Foreground and Background are equal but changed
            1 => IANSIControl::ATTR_BG_RGB_TPL . ' ',

            // Foreground and Background unequal, foreground unchanged
            2 => IANSIControl::ATTR_BG_RGB_TPL . ICustomChars::MAP[0x80],

            // Foreground and Background equal, foreground unchanged
            3 => IANSIControl::ATTR_BG_RGB_TPL . ' ',

            // Foreground and Background unequal, background unchanged
            4 => IANSIControl::ATTR_FG_RGB_TPL . ICustomChars::MAP[0x80],

            // Foreground and Backgrounc equal, foreground unchanged
            5 => IANSIControl::ATTR_BG_RGB_TPL . ' ',

            // Foreground and Background unequal, unchanged
            6 => ICustomChars::MAP[0x80],

            // Foreground and background unequal, unchanged
            7 => ' '
        ];

        while (($sInput = $this->receivePixelData($iExpectSize))) {

            $iGotSize = strlen($sInput);
            while ($iGotSize < $iExpectSize) {
                usleep(100);
                $sInput .= $this->receivePixelData($iExpectSize - $iGotSize);
                $iGotSize = strlen($sInput);
                ++$iShortReads;
            }

            $this->beginRedraw();
            $aPixels     = array_values(unpack('V*', $sInput));
            $sRawBuffer  = IANSIControl::CRSR_TOP_LEFT;
            $iEvenOffset = 0;
            $iOddOffset  = $this->iWidth;

            $iLastBackRGB = 0;
            $iLastForeRGB = 0;

            for ($iRow = 0; $iRow < $this->iHeight; $iRow += 2) {
                $i = $this->iWidth;
                while ($i--) {
                    $iForeRGB  = $aPixels[$iEvenOffset++];
                    $iBackRGB  = $aPixels[$iOddOffset++];
                    $iCase     = (int)($iForeRGB == $iBackRGB) | (int)($iForeRGB == $iLastForeRGB) << 1 | (int)($iBackRGB == $iLastBackRGB) << 2;
                    $sTemplate = $aTemplates[$iCase];
                    switch ($iCase) {
                        case 1:
                        //case 2: //TODO - why does this glitch?
                        case 3:
                        case 5:
                            $sRawBuffer .= sprintf(
                                $sTemplate,
                                $iBackRGB >> 16,
                                ($iBackRGB >> 8) & 0xFF,
                                ($iBackRGB & 0xFF)
                            );
                            break;
                        case 4:
                            $sRawBuffer .= sprintf(
                                $sTemplate,
                                $iForeRGB >> 16,
                                ($iForeRGB >> 8) & 0xFF,
                                ($iForeRGB & 0xFF)
                            );
                            break;
                        case 6:
                        case 7:
                            $sRawBuffer .= $sTemplate;
                            break;
                        case 0:
                        default:
                            $sRawBuffer .= sprintf(
                                $aTemplates[0],
                                $iForeRGB >> 16,
                                ($iForeRGB >> 8) & 0xFF,
                                ($iForeRGB & 0xFF),
                                $iBackRGB >> 16,
                                ($iBackRGB >> 8) & 0xFF,
                                ($iBackRGB & 0xFF)
                            );

                    }
                    $iLastForeRGB = $iForeRGB;
                    $iLastBackRGB = $iBackRGB;

                }
                $iEvenOffset += $this->iWidth;
                $iOddOffset  += $this->iWidth;
                $sRawBuffer .= "\n";
            }
            ob_start();
            echo $sRawBuffer . IANSIControl::ATTR_RESET;
            ob_end_flush();
            $this->endRedraw();
        }
        echo "\n";
        $this->reportRedraw("Subprocess");
        echo "\nShort reads: " . $iShortReads . "\n";
    }

}
