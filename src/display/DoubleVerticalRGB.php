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

    use TPixelled, TInstrumented;

    private array $aSocketPair = [];

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
        socket_close($this->aSocketPair[1]);
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
        $iShortReads = 0;
        while (($sInput = socket_read($this->aSocketPair[0], $iExpectSize, PHP_BINARY_READ))) {

            $iGotSize = strlen($sInput);
            while ($iGotSize < $iExpectSize) {
                usleep(100);
                $sInput .= socket_read($this->aSocketPair[0], $iExpectSize - $iGotSize, PHP_BINARY_READ);
                $iGotSize = strlen($sInput);
                ++$iShortReads;
            }

            $this->beginRedraw();
            $aPixels     = array_values(unpack('V*', $sInput));
            $sRawBuffer  = IANSIControl::CRSR_TOP_LEFT;
            $iEvenOffset = 0;
            $iOddOffset  = $this->iWidth;

            // Todo optimise for cases where either value is unchanged
            $sTemplate   = IANSIControl::ATTR_FG_RGB_TPL . IANSIControl::ATTR_BG_RGB_TPL . ICustomChars::MAP[0x80];

            for ($iRow = 0; $iRow < $this->iHeight; $iRow += 2) {
                $i = $this->iWidth;
                while ($i--) {
                    $iForeRGB = $aPixels[$iEvenOffset++];
                    $iBackRGB = $aPixels[$iOddOffset++];
                    $sRawBuffer .= sprintf(
                        $sTemplate,
                        $iForeRGB >> 16,
                        ($iForeRGB >> 8) & 0xFF,
                        ($iForeRGB & 0xFF),
                        $iBackRGB >> 16,
                        ($iBackRGB >> 8) & 0xFF,
                        ($iBackRGB & 0xFF),
                    );
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
        socket_close($this->aSocketPair[0]);
        echo "\n";
        $this->reportRedraw("Subprocess");
        echo "\nShort reads: " . $iShortReads . "\n";
        exit();
    }
}
