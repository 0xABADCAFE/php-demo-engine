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

    use TASCIIArt, TPixelled, TInstrumented;

    private array $aLineBreaks = [];
    private array $aSocketPair = [];

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
        socket_close($this->aSocketPair[1]);
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
        $sData = pack('V*', ...$this->oPixels);
        socket_write($this->aSocketPair[1], $sData, strlen($sData));
        $this->endRedraw();
        return $this;
    }

    /**
     * Initialise the asynchronous process and a socket pair for IPC.
     */
    private function initAsyncProcess() {
        if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $this->aSocketPair)) {
            throw new \Exception("Could not create socket pair");
        }
        $iProcessID = pcntl_fork();
        if (-1 == $iProcessID) {
            socket_close($this->aSocketPair[0]);
            socket_close($this->aSocketPair[1]);
            throw new \Exception("Couldn't create sub process");
        }
        if (0 == $iProcessID) {
            $this->runSubprocess();
        } else {
            socket_close($this->aSocketPair[0]);
        }
    }

    /**
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    private function runSubprocess() {
        socket_close($this->aSocketPair[1]);
        $sInput  = '';
        $iExpectSize = $this->iWidth * $this->iHeight * 4;
        $sTemplate   = IANSIControl::ATTR_BG_RGB_TPL;
        while (($sInput = socket_read($this->aSocketPair[0], $iExpectSize, PHP_BINARY_READ))) {
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
        socket_close($this->aSocketPair[0]);
        $this->reportRedraw("Subprocess");
        exit();
    }
}
