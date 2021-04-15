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
 * AsyncRGB
 *
 * Variation on BasicRGB that does the pixel to ANSI encoding and output on a subprocess. This frees up the main process
 * significantly on multicore systems. As an added bonus, it also implements IASCIIArt to allow overdraw.
 */
class AsyncRGB extends Base implements IPixelled, IASCIIArt {

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
        while (($sInput = $this->receivePixelData($iExpectSize))) {
            $this->beginRedraw();
            $aPixels    = unpack('V*', $sInput);
            $sRawBuffer = IANSIControl::CRSR_TOP_LEFT;
            $iLastRGB   = 0;
            $i          = 0;
            foreach ($aPixels as $iCRGB) {
                $sRawBuffer .= $this->aLineBreaks[$i++] ?? '';
                $iRGB        = $iCRGB & 0xFFFFFF;
                $iCharCode   = $iCRGB >> 24;
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
        }
        $this->reportRedraw("Subprocess");
    }

}
