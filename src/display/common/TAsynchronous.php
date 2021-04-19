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
 * Common implementation for subprocess display rendering
 */
trait TAsynchronous {

    /**
     * @var Socket[2]|resource[2] - Socket on php8
     */
    private array $aSocketPair = [];

    /**
     * Construct the required message header, containing the following 32-bit fields
     * { magic, command, size, check }
     *
     * @param  int $iCommand
     * @param  int $iSize
     * @return string
     */
    private function makeMessageHeader(int $iCommand, int $iSize) : string {
        return pack(
            'V4',
            IAsynchronous::HEADER_MAGIC,
            $iCommand,
            $iSize,
            IAsynchronous::HEADER_MAGIC ^ $iCommand ^ $iSize
        );
    }

    /**
     * Send a message containing the updated pixel buffer
     *
     * @param SPLFixedArray $oPixels
     * @param int           $iDataFormat
     */
    private function sendNewFrameMessage(SPLFixedArray $oPixels, int $iDataFormat) {
        if (!isset(IAsynchronous::DATA_FORMAT_MAP[$iDataFormat])) {
            throw new \InvalidArgumentException();
        }
        $iSize = count($oPixels) * $iDataFormat;
        $sData = $this->makeMessageHeader(IAsynchronous::MESSAGE_NEW_FRAME, $iSize) . pack(
            IAsynchronous::DATA_FORMAT_MAP[$iDataFormat],
            ...$oPixels
        );
        socket_write($this->aSocketPair[1], $sData, IAsynchronous::HEADER_SIZE + $iSize);
    }

    /**
     * Send a message with a new write mask to use
     *
     * @param int $iWriteMask
     */
    private function sendSetWritemaskMessage(int $iWriteMask) {
        $sData = $this->makeMessageHeader(IAsynchronous::MESSAGE_SET_WRITEMASK, 8) . pack('Q', $iWriteMask);
        socket_write($this->aSocketPair[1], $sData, IAsynchronous::HEADER_SIZE + 8);
    }

    /**
     * Send an arbitrary raw message.
     *
     * @param int    $iCommand
     * @param string $sRawData
     */
    private function sendRawMessage(int $iCommand, string $sRawData) {
        $iSize = strlen($sRawData);
        $sData = $this->makeMessageHeader($iCommand, $iSize) . $sRawData;
        socket_write($this->aSocketPair[1], $sData, IAsynchronous::HEADER_SIZE + $iSize);
    }

    /**
     * Attempt to receive a message header. If successful and the message contains
     * additional data, we expect to have to recieve it immediatelu afterwards/
     *
     * @return object|null { int $iMagic, $iCommand, $iSize, $iCheck }
     */
    private function receiveMessageHeader() : ?object {
        $sData   = $this->receiveData(IAsynchronous::HEADER_SIZE);
        if (empty($sData)) {
            return null;
        }
        $oHeader = unpack(
            'ViMagic/ViCommand/ViSize/ViCheck',
            $sData
        );
        if (false === $oHeader) {
            throw new \Exception("Could not read message header");
        }
        $oHeader = (object)$oHeader;
        if (
            IAsynchronous::HEADER_MAGIC !== $oHeader->iMagic ||
            ($oHeader->iMagic ^ $oHeader->iCommand ^ $oHeader->iSize) !== $oHeader->iCheck
        ) {
            throw new \Exception("Invalid header definition/check");
        }
        return $oHeader;
    }

    /**
     * Try to receive a given sized chunk of data.
     */
    private function receiveData(int $iExpectSize, int $iAttempts = 3) : string {
        $sData     = socket_read($this->aSocketPair[0], $iExpectSize, PHP_BINARY_READ);
        $iGotSize  = strlen($sData);
        while ($iGotSize < $iExpectSize && $iAttempts--) {
            usleep(100);
            $sData .= socket_read($this->aSocketPair[0], $iExpectSize - $iGotSize);
            $iGotSize = strlen($sData);
        }
        if (0 == $iAttempts) {
            throw new \Exception("Gave up attempting to read " . $iExpectSize . " bytes");
        }
        return $sData;
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
            $this->closeSocket(0);
            $this->closeSocket(1);
            throw new \Exception("Couldn't create sub process");
        }
        if (0 == $iProcessID) {
            $this->runSubprocess();
        } else {
            $this->closeSocket(0);
        }
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
        $this->closeSocket(1);
        $this->subprocessRenderLoop();
        $this->closeSocket(0);
        $this->reportRedraw("Subprocess");
        exit();
    }


    /**
     * Safely close and dispose of an enumerated socket.
     *
     * @param int $i - which enumerated socket to close
     */
    private function closeSocket(int $i) {
        if (isset($this->aSocketPair[$i])) {
            socket_close($this->aSocketPair[$i]);
            unset($this->aSocketPair[$i]);
        }
    }
}
