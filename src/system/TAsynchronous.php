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

namespace ABadCafe\PDE\System;
use ABadCafe\PDE;

/**
 * TAsynchronous
 *
 * Common implementation for asynchronous processes. Includes a messaging system for IPC.
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
     * @param  int $iCommand  - enumerated command
     * @param  int $iSize     - size of aditional data
     * @return string
     */
    private function makeMessageHeader(int $iCommand, int $iSize): string {
        return pack(
            'V4',
            IAsynchronous::HEADER_MAGIC,
            $iCommand,
            $iSize,
            IAsynchronous::HEADER_MAGIC ^ $iCommand ^ $iSize
        );
    }

    /**
     * Send an arbitrary raw message.
     *
     * @param int    $iCommand
     * @param string $sRawData
     * @param int    $iProcess - which process is sending the message
     */
    private function sendRawMessage(
        int    $iCommand,
        string $sRawData = '',
        int    $iProcess = IAsynchronous::ID_PARENT
    ): void {
        $iSize = strlen($sRawData);
        $sMessageData = $this->makeMessageHeader($iCommand, $iSize) . $sRawData;
        socket_write($this->aSocketPair[$iProcess], $sMessageData, IAsynchronous::HEADER_SIZE + $iSize);
    }

    /**
     * Attempt to receive a message header. If successful and the message contains
     * additional data, we expect to have to recieve it immediatelu afterwards/
     *
     * @param  int $iProcess - which process is receiving the data
     * @return \stdClass|null { int $iMagic, $iCommand, $iSize, $iCheck }
     */
    private function receiveMessageHeader(int $iProcess = IAsynchronous::ID_CHILD): ?\stdClass {
        $sMessageData = $this->receiveData(IAsynchronous::HEADER_SIZE, $iProcess);
        if (empty($sMessageData)) {
            return null;
        }
        $oHeader = unpack(
            'ViMagic/ViCommand/ViSize/ViCheck',
            $sMessageData
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
     *
     * @param int  $iExpectedSize - how many bytes we expect
     * @param int  $iAttempts     - number of retries on a short read
     * @param int  $iProcess      - which process is receiving the data
     */
    private function receiveData(int $iExpectSize, int $iProcess = IAsynchronous::ID_CHILD): string {
        $sMessageData     = socket_read($this->aSocketPair[$iProcess], $iExpectSize, PHP_BINARY_READ);
        $iGotSize  = strlen($sMessageData);
        $iAttempts = IAsynchronous::MAX_RETRIES;
        while ($iGotSize < $iExpectSize && $iAttempts--) {
            usleep(IAsynchronous::RETRY_PAUSE);
            $sMessageData .= socket_read($this->aSocketPair[$iProcess], $iExpectSize - $iGotSize);
            $iGotSize = strlen($sMessageData);
        }

        if (0 == $iAttempts) {
            throw new \Exception("Gave up attempting to read " . $iExpectSize . " bytes");
        }
        return $sMessageData;
    }

    /**
     * Send a basic response code (integer) back to the parent for commands that require them.
     *
     * @param  int $iResponseCode - 32-bit integer response code
     * @param  int $iProcess      - which process is sending the response
     */
    private function sendResponseCode(int $iResponseCode, int $iProcess = IAsynchronous::ID_CHILD): self {
        socket_write(
            $this->aSocketPair[$iProcess],
            pack('V', $iResponseCode)
        );
        return $this;
    }

    /**
     * Initialise the asynchronous process and a socket pair for IPC.
     */
    private function initAsyncProcess(): void {
        if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $this->aSocketPair)) {
            throw new \Exception("Could not create socket pair");
        }
        $iProcessID = pcntl_fork();
        if (-1 == $iProcessID) {
            $this->closeSocket(IAsynchronous::ID_CHILD);
            $this->closeSocket(IAsynchronous::ID_PARENT);
            throw new \Exception(self::class . "Couldn't create sub process");
        }
        if (0 == $iProcessID) {
            $this->closeSocket(IAsynchronous::ID_PARENT);
            $this->runSubprocess();
            $this->closeSocket(IAsynchronous::ID_CHILD);
            exit();
        } else {
            $this->closeSocket(IAsynchronous::ID_CHILD);
        }
    }

    /**
     * Class that incorporates the trait needs to implement this.
     */
    protected abstract function runSubprocess(): void;

    /**
     * Safely close and dispose of an enumerated socket.
     *
     * @param int $iProcess - which processes socket to close
     */
    private function closeSocket(int $iProcess): void {
        if (isset($this->aSocketPair[$iProcess])) {
            socket_close($this->aSocketPair[$iProcess]);
            unset($this->aSocketPair[$iProcess]);
        }
    }
}
