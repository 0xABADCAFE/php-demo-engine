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
 * BaseAsyncASCIIWithRGB
 *
 * Common base class for RGBASCII, ASCIIOverRGB and RGBASCIIOverRGB, all of which support ASCII Art and RGB.
 */
abstract class BaseAsyncASCIIWithRGB extends Base implements IPixelled, IASCIIArt, IAsynchronous {

    use TASCIIArt, TPixelled, TInstrumented, TAsynchronous;

    protected array $aLineBreaks = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);

        $aLineBreaks   = range(0, $iWidth * $iHeight, $iWidth);
        unset($aLineBreaks[0]);
        $this->aLineBreaks = array_fill_keys($aLineBreaks, "\n");

        // Initialise the subprocess now as it only needs access to the properties evaluated to now.
        $this->initAsyncProcess();
        $this->initASCIIBuffer($iWidth, $iHeight);
        $this->initPixelBuffer($iWidth, $iHeight, static::PIXEL_FORMAT);
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
    public function clear() : self {
        $this->resetASCIIBuffer();
        $this->resetPixelBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $this->beginRedraw();
        $this->preparePixels();
        $this->sendNewFrameMessage($this->oPixels, static::DATA_FORMAT);
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
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    protected function subprocessRenderLoop() {
        ini_set('output_buffering', 'true');
        $sInput   = '';
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
                    $this->beginRedraw();
                    $this->drawFrame($sData, $sInitial);
                    $this->endRedraw();
                    break;
                case self::MESSAGE_WAIT_FOR_FRAME:
                    $this->sendResponseCode(self::RESPONSE_OK);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Draw a frame of pixels to the console. This typically involves decoding a packed array of
     * integer data and converting it into ANSI/ASCII for display.
     *
     * @param string $sData     - The raw binary data representing the pixel array
     * @param string $sInitial  - The first part of the output, e.g. reset the cursor position etc.
     */
    protected function drawFrame(string $sData, string $sInitial) {
        $aPixels    = unpack(self::DATA_FORMAT_MAP[static::DATA_FORMAT], $sData);
        $sRawBuffer = $sInitial;
        $iLastRGB   = 0xFF000000;
        $i          = 0;
        foreach ($aPixels as $iCRGB) {
            $sRawBuffer .= $this->aLineBreaks[$i++] ?? '';
            $iCharCode   = $iCRGB >> 24;
            $iRGB        = $iCRGB & $this->iRGBWriteMask;
            $sTextChar   = ICustomChars::MAP[$iCharCode] ?? chr($iCharCode);
            if ($iRGB !== $iLastRGB) {
                $sRawBuffer .= sprintf(
                    static::ATTR_TEMPLATE,
                    ($iRGB >> 16) & 0xFF, // Red
                    ($iRGB >> 8) & 0xFF,  // Green
                    ($iRGB & 0xFF)        // Blue
                ) . $sTextChar;
                $iLastRGB = $iRGB;
            } else {
                $sRawBuffer .= $sTextChar;
            }
        }
        // Make sure we output the data in one blast to try to mitigate partial redraw.
        ob_start(null, 0);
        echo $sRawBuffer;
        ob_end_flush();
    }

    /**
     * Prepare the pixel array before submission to the asynchronous process. This is split out
     * so that it can be overridden.
     */
    protected function preparePixels() {
        $j = 0;
        foreach ($this->oPixels as $i => $iRGB) {
            $j += (int)isset($this->aLineBreaks[$i]);
            $this->oPixels[$i] = ord($this->sRawBuffer[$j++]) << 24 | $iRGB;
        }
    }
}
