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
 * ASCIIOverRGB
 *
 * Variation on BasicRGB that does the pixel to ANSI encoding and output on a subprocess. This frees up the main process
 * significantly on multicore systems. As an added bonus, it also implements IASCIIArt to allow overdraw.
 */
class ASCIIOverRGB extends Base implements IPixelled, IASCIIArt, IAsynchronous {

    use TASCIIArt, TPixelled, TInstrumented, TAsynchronous;

    private array $aLineBreaks = [];

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
        $j = 0;
        foreach ($this->oPixels as $i => $iRGB) {
            $j += (int)isset($this->aLineBreaks[$i]);
            $this->oPixels[$i] = ord($this->sRawBuffer[$j++]) << 24 | $iRGB;
        }
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
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    protected function subprocessRenderLoop() {
        $sInput  = '';
        $sTemplate   = IANSIControl::ATTR_BG_RGB_TPL;
        $sInitial    = IANSIControl::CRSR_TOP_LEFT . sprintf(IANSIControl::ATTR_BG_RGB_TPL, 0, 0, 0);
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
                    $aPixels    = unpack('V*', $sData);
                    $sRawBuffer = $sInitial;
                    $iLastRGB   = 0;
                    $i          = 0;
                    foreach ($aPixels as $iCRGB) {
                        $sRawBuffer .= $this->aLineBreaks[$i++] ?? '';
                        $iCharCode   = $iCRGB >> 24;
                        $iRGB        = $iCRGB & $this->iRGBWriteMask;
                        $sChar       = ICustomChars::MAP[$iCharCode] ?? chr($iCharCode);
                        if ($iRGB !== $iLastRGB) {
                            $sRawBuffer .= sprintf(
                                $sTemplate,
                                ($iRGB >> 16) & 0xFF, // Red
                                ($iRGB >> 8) & 0xFF,  // Green
                                ($iRGB & 0xFF)        // Blue
                            ) . $sChar;
                            $iLastRGB = $iRGB;
                        } else {
                            $sRawBuffer .= $sChar;
                        }
                    }
                    echo $sRawBuffer . IANSIControl::ATTR_RESET;
                    $this->endRedraw();
                    break;
                default:
                    break;
            }
        }
    }


}
