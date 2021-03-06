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
use function ABadCafe\PDE\dprintf, \array_fill, \exec, \fclose, \fwrite, \is_resource, \pack, \proc_close, \proc_open;

/**
 * APlay
 *
 * Simple pipe wrapper for aplay
 */
abstract class Piped implements Audio\IPCMOutput {

    const BUFFER_SIZE = 1024;

    /** @var resource|null $rOutput  */
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
    private array $aPipes = [];

    /** @var class-string|null $sPlayerClass */
    private static ?string $sPlayerClass = null;

    /**
     * Factory for piped playback. Perfers APlay > Sox > None
     */
    public static function create(): self {
        while (null === self::$sPlayerClass) {
            $sAPlay = exec('which aplay');
            if (!empty($sAPlay)) {
                dprintf("Found %s\n", $sAPlay);
                self::$sPlayerClass = APlay::class;
                break;
            }
            $sSoxPlay = exec('which play');
            if (!empty($sSoxPlay)) {
                dprintf("Found %s\n", $sSoxPlay);
                self::$sPlayerClass = Sox::class;
                break;
            }
            dprintf("No available pipe output\n");
            self::$sPlayerClass = None::class;
        }
        /** @var self $oInstance */
        $oInstance = new self::$sPlayerClass;
        return $oInstance;
    }


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
    public function open(): void {
        if (null === $this->rOutput) {
            $sCommand = $this->createOutputCommand();
            $rProc = proc_open($sCommand, $this->aPipeDescriptors, $this->aPipes);
            if (is_resource($rProc)) {
                $this->rOutput = $rProc;
                dprintf("Audio pipe: %s\n", $sCommand);
                $this->pushSilence();
                return;
            }
        }
        throw new \Exception();
    }

    /**
     * Write a signal packet. This involves scaling, quantising values and limiting them before writing.
     *
     * @param Audio\Signal\Packet $oPacket
     */
    public function write(Audio\Signal\Packet $oPacket): void {
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
    public function close(): void {
        if (null !== $this->rOutput) {
            $this->pushSilence();
            proc_close($this->rOutput); // @phpstan-ignore-line - false positive
            foreach ($this->aPipes as $rPipe) {
                if (is_resource($rPipe)) {
                    fclose($rPipe);
                }
            }
            $this->rOutput = null;
            $this->aPipes  = [];
        }
    }

    /**
     * Pushes a block of silence to bookend opening and closing.
     */
    protected function pushSilence(): void {
        $aOutputBuffer = array_fill(0, Audio\IConfig::PACKET_SIZE, 0);
        for ($i = 0; $i < 10; ++$i) {
            fwrite($this->aPipes[0], pack('v*', ...$aOutputBuffer));
        }
    }

    /**
     * Create the necessary command for the piped subprocess.
     *
     * @return string
     */
    protected abstract function createOutputCommand(): string;
}
