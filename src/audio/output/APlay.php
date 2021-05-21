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

namespace ABadCafe\PDE\Audio\Output;
use ABadCafe\PDE\Audio;
use function ABadCafe\PDE\dprintf;

/**
 * APlay
 *
 * Simple pipe wrapper for aplay
 */
class APlay implements Audio\IPCMOutput {

    const
        BUFFER_SIZE = 128,
        FORMAT      = 'S16_LE'
    ;

    /**
     * var resource|null $rOutput
     */
    private $rOutput = null;

    /** @var int[] $aOutputBuffer */
    private array $aOutputBuffer = [];

    /** @var string[][] $aPipeDescriptors */
    private array $aPipeDescriptors  = [
        0 => ['pipe', 'r'],
        1 => ['file', '/dev/null', 'a'],
        2 => ['file', '/dev/null', 'a']
    ];

    /** @var resource[] $aPipes */
    private array
        $aPipes = []
    ;

    /**
     * Constructor
     */
    public function __construct() {
        $this->aOutputBuffer = array_fill(0, Audio\IConfig::PACKET_SIZE, 0);
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close();
    }


    /**
     * Open the output stream. Throws an exception if it is not possible to open aplay for output.
     *
     * @throws \Exception
     */
    public function open() {
        $sCommand = sprintf(
            'aplay -c1 -f %s -r%d --buffer-size=%d -',
            self::FORMAT,
            Audio\IConfig::PROCESS_RATE,
            self::BUFFER_SIZE
        );

        if (
            $this->rOutput ||
            !($this->rOutput = proc_open($sCommand, $this->aPipeDescriptors, $this->aPipes))
        ) {
            throw new \Exception();
        } else {
            dprintf("aplay: %s\n", $sCommand);
        }
        $this->pushSilence();
    }

    /**
     * Write a signal packet. This involves scaling, quantising values and limiting them before writing.
     *
     * @param Signal\Packet $oPacket
     */
    public function write(Audio\Signal\Packet $oPacket) {
        // Quantize and clamp
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $iValue = (int)(self::SCALE * $oPacket[$i]);
            $this->aOutputBuffer[$i] = ($iValue < self::MIN_LEVEL) ?
                self::MIN_LEVEL : (
                ($iValue > self::MAX_LEVEL) ?
                    self::MAX_LEVEL :
                    $iValue
                );
        }
        fwrite($this->aPipes[0], pack('v*', ...$this->aOutputBuffer));
    }

    /**
     * Close down the output handle and subprocess.
     */
    public function close() {
        if ($this->rOutput) {
            $this->pushSilence();
            proc_close($this->rOutput);
            foreach ($this->aPipes as $rPipe) {
                if (is_resource($rPipe)) {
                    fclose($rPipe);
                }
            }
            $this->rOutput = null;
            $this->aPipes  = [];
        }
    }

    protected function pushSilence() {
        $aOutputBuffer = array_fill(0, Audio\IConfig::PACKET_SIZE, 0);
        for ($i = 0; $i < 10; ++$i) {
            fwrite($this->aPipes[0], pack('v*', ...$aOutputBuffer));
        }
    }

}
