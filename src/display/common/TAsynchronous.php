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
 * TAsynchronous
 *
 * Common implementation for subprocess display rendering.
 */
trait TAsynchronous {

    use PDE\System\TAsynchronous;

    /**
     * @implements IDisplay::waitForFrame()
     */
    public function waitForFrame() : PDE\IDisplay {
        //return $this;
        $this->sendRawMessage(IAsynchronous::MESSAGE_WAIT_FOR_FRAME);

        // Now wait for the message to come back. We don't really care about the
        // actual response code, the messaging itself is a synchronisation.
        $sResponse = \socket_read(
            $this->aSocketPair[IAsynchronous::ID_PARENT],
            4,
            PHP_BINARY_READ
        );
        echo IANSIControl::ATTR_RESET;
        return $this;
    }


    /**
     * Send a message containing the updated pixel buffer
     *
     * @param SPLFixedArray $oPixels
     * @param int           $iDataFormat
     */
    protected function sendNewFrameMessage(SPLFixedArray $oPixels, int $iDataFormat) {
        if (!isset(IAsynchronous::DATA_FORMAT_MAP[$iDataFormat])) {
            throw new \InvalidArgumentException();
        }
        $iSize = \count($oPixels) * $iDataFormat;
        $sMessageData = $this->makeMessageHeader(IAsynchronous::MESSAGE_NEW_FRAME, $iSize) . \pack(
            IAsynchronous::DATA_FORMAT_MAP[$iDataFormat],
            ...$oPixels
        );
        \socket_write($this->aSocketPair[IAsynchronous::ID_PARENT], $sMessageData, IAsynchronous::HEADER_SIZE + $iSize);
    }

    /**
     * Send a message with a new write mask to use
     *
     * @param int $iWriteMask
     */
    private function sendSetWritemaskMessage(int $iWriteMask) {
        $sMessageData = $this->makeMessageHeader(IAsynchronous::MESSAGE_SET_WRITEMASK, 8) . \pack('Q', $iWriteMask);
        \socket_write($this->aSocketPair[IAsynchronous::ID_PARENT], $sMessageData, IAsynchronous::HEADER_SIZE + 8);
    }

    /**
     * Send a message with a new fixed foreground colour to use
     *
     * @param int $iWriteMask
     */
    private function sendSetForegroundColour(int $iColour) {
        $sMessageData = $this->makeMessageHeader(IAsynchronous::MESSAGE_SET_FG_COLOUR, 4) . \pack('V', $iColour);
        \socket_write($this->aSocketPair[IAsynchronous::ID_PARENT], $sMessageData, IAsynchronous::HEADER_SIZE + 4);
    }

    /**
     * Send a message with a new fixed background colour to use
     *
     * @param int $iWriteMask
     */
    private function sendSetBackgroundColour(int $iColour) {
        $sMessageData = $this->makeMessageHeader(IAsynchronous::MESSAGE_SET_BG_COLOUR, 4) . \pack('V', $iColour);
        \socket_write($this->aSocketPair[IAsynchronous::ID_PARENT], $sMessageData, IAsynchronous::HEADER_SIZE + 4);
    }


    /**
     * Class that incorporates the trait needs to implement this.
     */
    protected abstract function subprocessRenderLoop();

    /**
     * Main subprocess loop. This sits and waits for data from the socket. When the data arrives
     * it decodes and prints it.
     */
    private function runSubprocess() {
        // In the unlikely event we run as root, bang up our priority
        if (0 === \posix_getuid()) {
            \proc_nice(-19);
        }
        $this->subprocessRenderLoop();
        $this->reportRedraw(self::class . " Subprocess");
    }


    /**
     * Safely close and dispose of an enumerated socket.
     *
     * @param int $iProcess - which processes socket to close
     */
    private function closeSocket(int $iProcess) {
        if (isset($this->aSocketPair[$iProcess])) {
            \socket_close($this->aSocketPair[$iProcess]);
            unset($this->aSocketPair[$iProcess]);
        }
    }
}
