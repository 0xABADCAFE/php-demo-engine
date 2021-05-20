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

namespace ABadCafe\PDE\Audio;

use function ABadCafe\PDE\dprintf;

/**
 * PCMOutput
 *
 * Simple pipe wrapper for aplay
 */
class PCMOutput {

    const
        SCALE       = 32767.0,
        MIN_LEVEL   = -32767,
        MAX_LEVEL   = 32767,
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
        $this->aOutputBuffer = array_fill(0, IConfig::PACKET_SIZE, 0);
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
            IConfig::PROCESS_RATE,
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
        // push a little silence
        fwrite($this->aPipes[0], pack('v*', ...$this->aOutputBuffer));
    }

    /**
     * Write a signal packet. This involves scaling, quantising values and limiting them before writing.
     *
     * @param Signal\Packet $oPacket
     */
    public function write(Signal\Packet $oPacket) {
        // Quantize and clamp
        for ($i = 0; $i < IConfig::PACKET_SIZE; ++$i) {
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
}
