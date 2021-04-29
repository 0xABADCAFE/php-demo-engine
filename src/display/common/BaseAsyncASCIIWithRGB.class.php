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

    const DEFAULT_PARAMETERS = [

        /**
         * Default background RGB code (hex)
         */
        'sBGColourRGB' => '000000',

        /**
         * Default foreground RGB code (hex)
         */
        'sFGColourRGB' => 'FFFFFF',

        /**
         * Default writemask code (hex)
         */
        'sMaskRGB'     => 'FFFFFF'
    ];

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

        $this->initFixedColours();
        $this->initAsyncProcess();
        $this->initASCIIBuffer($iWidth, $iHeight);
        $this->initPixelBuffer($iWidth, $iHeight, static::PIXEL_FORMAT);
        $this->reset();
    }

    /**
     * Destructor. Ensured our end of the socket pair is closed.
     */
    public function __destruct() {
        $this->closeSocket(self::ID_PARENT);
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
    public function setParameters(array $aParameters) : self {
        $oParameters = $this->filterRawParameters($aParameters);
        if (isset($oParameters->sFGColourRGB)) {
            $this->setForegroundColour((int)base_convert($oParameters->sFGColourRGB, 16, 10));
        }
        if (isset($oParameters->sBGColourRGB)) {
            $this->setBackgroundColour((int)base_convert($oParameters->sBGColourRGB, 16, 10));
        }
        if (isset($oParameters->sMaskRGB)) {
            $this->setRGBWriteMask((int)base_convert($oParameters->sMaskRGB, 16, 10));
        }
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
     * Set the default foreground ANSI colour to use. Replaces the trait version as we want to use
     * bonafide RGB values here.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setForegroundColour(int $iColour) : self {
        $iColour &= 0xFFFFFF;
        if ($iColour != $this->iFGColour) {
            $this->iFGColour = $iColour;
            $this->sendSetForegroundColour($iColour);
        }
        return $this;
    }

    /**
     * Set the default background ANSI colour to use. Replaces the trait version as we want to use
     * bonafide RGB values here.
     *
     * @param  int  $iColour
     * @return self
     */
    public function setBackgroundColour(int $iColour) : self {
        $iColour &= 0xFFFFFF;
        if ($iColour != $this->iBGColour) {
            $this->iBGColour = $iColour;
            $this->sendSetBackgroundColour($iColour);
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
        $sInitial = IANSIControl::CRSR_TOP_LEFT;
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
                    $this->drawFrame($sData, $sInitial . $this->sFGColour . $this->sBGColour);
                    $this->endRedraw();
                    break;

                case self::MESSAGE_WAIT_FOR_FRAME:
                    $this->sendResponseCode(self::RESPONSE_OK);
                    break;

                case self::MESSAGE_SET_FG_COLOUR:
                    $aData = unpack('V', $sData);
                    $iRGB  = reset($aData);
                    $this->sFGColour = sprintf(
                        IANSIControl::ATTR_FG_RGB_TPL,
                        ($iRGB >> 16),
                        (($iRGB >> 8) & 0xFF),
                        ($iRGB & 0xFF)
                    );
                    break;

                case self::MESSAGE_SET_BG_COLOUR:
                    $aData = unpack('V', $sData);
                    $iRGB  = reset($aData);
                    $this->sBGColour = sprintf(
                        IANSIControl::ATTR_BG_RGB_TPL,
                        ($iRGB >> 16),
                        (($iRGB >> 8) & 0xFF),
                        ($iRGB & 0xFF)
                    );
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
