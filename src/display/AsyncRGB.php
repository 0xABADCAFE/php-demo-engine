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
class AsyncRGB implements PDE\IDisplay, IPixelled, IASCIIArt {

    private int           $iWidth, $iHeight, $iMaxLuma = self::DEF_MAX_LUMA;
    private SPLFixedArray $oPixels, $oNewPixels;
    private string        $sRawBuffer, $sNewRawBuffer, $sLumaChars = self::DEF_LUMA_CHAR;
    private array         $aLineBreaks = [];
    private int           $iTotalRedrawCount = 0;
    private float         $fTotalRedrawTime  = 0.0;
    private array         $aSocketPair = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        if ($iWidth < self::I_MIN_WIDTH || $iHeight < self::I_MIN_HEIGHT) {
            throw new \RangeException('Invalid dimensions');
        }

        $this->iWidth        = $iWidth;
        $this->iHeight       = $iHeight;

        $aLineBreaks   = range(0, $iWidth * $iHeight, $iWidth);
        unset($aLineBreaks[0]);
        $this->aLineBreaks = array_fill_keys($aLineBreaks, "\n");

        // Initialise the subprocess now as it only needs access to the properties evaluated to now.
        $this->initAsyncProcess();

        $this->sRawBuffer    = // drop through
        $this->sNewRawBuffer = str_repeat(str_repeat(' ', $iWidth) . "\n", $iHeight);

        $this->oPixels       = clone // drop through
        $this->oNewPixels    = SPLFixedArray::fromArray(array_fill(0, $iWidth * $iHeight, 0));
        $this->reset();
    }

    /**
     * Destructor. Ensured our end of the socket pair is closed.
     */
    public function __destruct() {
        socket_close($this->aSocketPair[1]);
        echo IANSIControl::CRSR_ON, "\n";
        printf(
            "Parent total redraw time: %.3f seconds, %d calls, %.2f ms/redraw\n",
            $this->fTotalRedrawTime,
            $this->iTotalRedrawCount,
            1000.0 * $this->fTotalRedrawTime / $this->iTotalRedrawCount
        );
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
        $this->oPixels    = clone $this->oNewPixels;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $fMark = microtime(true);
        $j = 0;
        foreach ($this->oPixels as $i => $iRGB) {
            $j += (int)isset($this->aLineBreaks[$i]);
            $this->oPixels[$i] = ord($this->sRawBuffer[$j++]) << 24 | $iRGB;
        }
        $sData = pack('V*', ...$this->oPixels);
        socket_write($this->aSocketPair[1], $sData, strlen($sData));
        $this->fTotalRedrawTime += microtime(true) - $fMark;
        ++$this->iTotalRedrawCount;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPixelBuffer() : SPLFixedArray {
        return $this->oPixels;
    }

    /**
     * @inheritDoc
     */
    public function getPixelFormat() : int {
        return self::PIX_ASCII_RGB2;
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
            $fMark      = microtime(true);
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
            echo $sRawBuffer . IANSIControl::ATTR_RESET . "\n";
            flush();
            $this->fTotalRedrawTime += microtime(true) - $fMark;
            ++$this->iTotalRedrawCount;
        }
        socket_close($this->aSocketPair[0]);
        printf(
            "Child total redraw time: %.3f seconds, %d calls, %.2f ms/redraw\n",
            $this->fTotalRedrawTime,
            $this->iTotalRedrawCount,
            1000.0 * $this->fTotalRedrawTime / $this->iTotalRedrawCount
        );
        exit();
    }
}
