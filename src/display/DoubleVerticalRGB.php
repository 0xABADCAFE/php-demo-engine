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
class DoubleVerticalRGB extends Base implements IPixelled, IAsynchronous {

    const DEFAULT_PARAMETERS = [
        /**
         * Default writemask code (hex)
         */
        'sMaskRGB'     => 'FFFFFF'
    ];

    /**
     * Maps a 3-bit change indicator to a suitable template for output.
     *
     * Bit 0 is set when the current foreground and background RGB values are different.
     * Bit 1 is set when the current and previous foreground RGB values are different.
     * Bit 2 is set when the current and previous background RGB values are different.
     */
    const ATTR_TEMPLATE = [
        // Nothing changed, foreground == background : No ANSI, whitespace
        0 => ' ',

        // Nothing changed, foreground != background : No ANSI, half-block
        1 => ICustomChars::MAP[0x80],

        // Foreground changed, background == foreground : ANSI BG, whitespace
        2 => IANSIControl::ATTR_BG_RGB_TPL . ' ',

        // Foreground changed, background != foreground : ANSI FG, half-block
        3 => IANSIControl::ATTR_FG_RGB_TPL . ICustomChars::MAP[0x80],

        // Background changed, background == foreground : ANSI BG, whitespace
        4 => IANSIControl::ATTR_BG_RGB_TPL . ' ',

        // Background changed, background != foreground : ANSI BG, half-block
        5 => IANSIControl::ATTR_BG_RGB_TPL .  ICustomChars::MAP[0x80],

        // Background and Foreground changed, background == foreground : ANSI FG + BG, whitespace
        6 => IANSIControl::ATTR_FG_RGB_TPL . IANSIControl::ATTR_BG_RGB_TPL . ' ',

        // Everything changed, foreground and background not equal : ANSI FG + BG, half-block
        7 => IANSIControl::ATTR_FG_RGB_TPL . IANSIControl::ATTR_BG_RGB_TPL . ICustomChars::MAP[0x80]
    ];

    use TPixelled, TInstrumented, TAsynchronous;

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        // Height must be even
        $iHeight &= ~1;
        parent::__construct($iWidth, $iHeight);

        // Initialise the subprocess now as it only needs access to the properties evaluated to now.
        $this->initAsyncProcess();
        $this->initPixelBuffer($iWidth, $iHeight, self::FORMAT_RGB);
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
        $this->sendNewFrameMessage($this->oPixels, self::DATA_FORMAT_32);
        $this->endRedraw();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setRGBWriteMask(int $iMask) : self {
        if ($iMask !== $this->iRGBWriteMask) {
            $this->iRGBWriteMask = $iMask;
            $this->sendSetWritemaskMessage($iMask);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setParameters(array $aParameters) : self {
        $oParameters = $this->filterRawParameters($aParameters);
        if (isset($oParameters->sMaskRGB)) {
            $this->setRGBWriteMask((int)base_convert($oParameters->sMaskRGB, 16, 10));
        }
        return $this;
    }

    /**
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    protected function subprocessRenderLoop() {
        ini_set('output_buffering', 'true');

        $sInitial = IANSIControl::CRSR_TOP_LEFT . sprintf(IANSIControl::ATTR_BG_RGB_TPL, 0, 0, 0);

        while (($oMessage = $this->receiveMessageHeader())) {

            // Get any expected data following the message header
            $sData = $oMessage->iSize > 0 ? $this->receiveData($oMessage->iSize) : null;

            switch ($oMessage->iCommand) {
                case self::MESSAGE_SET_WRITEMASK:
                    $aData = unpack('Q', $sData);
                    $this->iRGBWriteMask = reset($aData);
                    break;

                case self::MESSAGE_NEW_FRAME:
                    $this->drawFrame($sData, $sInitial);
                    break;
                case self::MESSAGE_WAIT_FOR_FRAME:
                    $this->sendResponseCode(self::RESPONSE_OK);
                    break;
                default:
                    break;
            }
        }
    }

    private function drawFrame(string $sData, string $sInitial) {
        $this->beginRedraw();
        $aPixels      = array_values(unpack('V*', $sData));
        $sRawBuffer   = $sInitial;
        $iEvenOffset  = 0;
        $iOddOffset   = $this->iWidth;
        $iLastBackRGB = 0xFF000000;
        $iLastForeRGB = 0xFF000000;
        $aTemplates   = self::ATTR_TEMPLATE;
        for ($iRow = 0; $iRow < $this->iHeight; $iRow += 2) {
            $i = $this->iWidth;
            while ($i--) {
                $iForeRGB  = $aPixels[$iEvenOffset++] & $this->iRGBWriteMask;
                $iBackRGB  = $aPixels[$iOddOffset++]  & $this->iRGBWriteMask;
                $iChanged  = (int)($iForeRGB != $iBackRGB)          | // Foreground and background differ
                             (int)($iForeRGB != $iLastForeRGB) << 1 | // Foreground has changed
                             (int)($iBackRGB != $iLastBackRGB) << 2;  // Background has changed
                $sTemplate = $aTemplates[$iChanged];

                switch ($iChanged) {
                    case 0:
                    case 1:
                        // No RGB changes
                        $sRawBuffer .= $sTemplate;
                        break;
                    case 2:
                    case 4:
                    case 5:
                        // Background RGB changes only
                        $sRawBuffer .= sprintf(
                            $sTemplate,
                            $iBackRGB >> 16,
                            ($iBackRGB >> 8) & 0xFF,
                            ($iBackRGB & 0xFF)
                        );
                        break;
                    case 3:
                        // Foreground RGB changes
                        $sRawBuffer .= sprintf(
                            $sTemplate,
                            $iForeRGB >> 16,
                            ($iForeRGB >> 8) & 0xFF,
                            ($iForeRGB & 0xFF)
                        );
                        break;
                    case 6:
                    case 7:
                        // Background and foreground changes
                        $sRawBuffer .= sprintf(
                            $sTemplate,
                            $iForeRGB >> 16,
                            ($iForeRGB >> 8) & 0xFF,
                            ($iForeRGB & 0xFF),
                            $iBackRGB >> 16,
                            ($iBackRGB >> 8) & 0xFF,
                            ($iBackRGB & 0xFF)
                        );
                        break;
                }
                $iLastForeRGB = $iForeRGB;
                $iLastBackRGB = $iBackRGB;
            }
            $iEvenOffset += $this->iWidth;
            $iOddOffset  += $this->iWidth;
            $sRawBuffer .= "\n";
        }
        ob_start(null, 0);
        echo $sRawBuffer;
        ob_end_flush();
        $this->endRedraw();
    }

}
