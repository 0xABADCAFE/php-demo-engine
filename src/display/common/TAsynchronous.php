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
     * Write the bixel buffer
     *
     * @param SPLFixedArray $oPixels
     */
    private function sendPixels(SPLFixedArray $oPixels) {
        $sData = pack('V*', ...$oPixels);
        socket_write($this->aSocketPair[1], $sData, strlen($sData));
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
     * @param int $iExpectSize
     * return string
     */
    private function receivePixelData(int $iExpectSize) {
        return socket_read($this->aSocketPair[0], $iExpectSize, PHP_BINARY_READ);
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
