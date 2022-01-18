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
use function ABadCafe\PDE\dprintf, \array_fill, \array_values, \date, \fclose, \fopen, \ftell, \fwrite, \is_resource, \pack, \rewind, \str_repeat;

/**
 * Wav
 *
 * Minimal implementation of the RIFF Wave standard for linear PCM
 */
class Wav implements Audio\IPCMOutput {

    const HEADER_SIZE = 44;

    const M_HEADER = [
        'sChunkID'       => 'RIFF', //  0: 4
        'iChunkSize'     => -1,     //  4: 4  (file - 8)
        'sFormat'        => 'WAVE', //  8: 4  Format ID
        'sSubChunk1ID'   => 'fmt ', // 12: 4
        'iSubChunk1Size' => 16,     // 16: 4
        'iAudioFormat'   => 1,      // 20: 2
        'iNumChannels'   => 1,      // 22: 2
        'iSampleRate'    => -1,     // 24: 4
        'iByteRate'      => -1,     // 28: 4
        'iBlockAlign'    => -1,     // 32: 2
        'iBitsPerSample' => 16,     // 34: 2
        'sSubChunk2ID'   => 'data', // 36: 4
        'iSubChunk2Size' => -1,     // 40: 4
    ];

    const S_HEADER_PACK = 'a4Va4a4VvvVVvva4V';

    /** @var resource|null $rOutput */
    private $rOutput = null;

    private int
        $iSampleRate     = Audio\IConfig::PROCESS_RATE,
        $iBitsPerSample  = 16,
        $iNumChannels    = 1
    ;

    private string $sPath;

    /** @var int[] $aOutputBuffer */
    private array $aOutputBuffer = [];

    /**
     * Constructor
     *
     * @param string $sPath
     */
    public function __construct(string $sPath) {
        $this->sPath = empty($sPath) ? (date('ymdHis') . '.wav') : $sPath;
        $this->aOutputBuffer = array_fill(0, Audio\IConfig::PACKET_SIZE, 0);
    }

    /**
     * Destructor, ensures output is closed
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * @inheritdoc
     */
    public function open(): void {
        if (null ===  $this->rOutput) {
            $rFile = fopen($this->sPath, 'wb');
            if (is_resource($rFile)) {
                $this->rOutput = $rFile;
                $this->reserveHeader();
                return;
            }
        }
        throw new \Exception();
    }

    /**
     * @inheritdoc
     */
    public function close(): void {
        if (null !== $this->rOutput) {
            $this->writeHeader();
            fclose($this->rOutput); // @phpstan-ignore-line - false positive
            $this->rOutput = null;
        }
    }

    /**
     * @inheritdoc
     */
    public function write(Audio\Signal\Packet $oPacket): void {
        if (null !== $this->rOutput) {
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
            fwrite($this->rOutput, pack('v*', ...$this->aOutputBuffer));
        }
    }

    /**
     * Reserve the header storage on opening the file
     */
    private function reserveHeader(): void {
        if (null !== $this->rOutput) {
            fwrite($this->rOutput, str_repeat('-', self::HEADER_SIZE));
        }
    }

    /**
     * Rewinds and writes the header on closing the file
     */
    private function writeHeader(): void {
        if (null !== $this->rOutput) {
            $aHeader     = self::M_HEADER;
            $iFileSize   = ftell($this->rOutput);
            $iBlockAlign = ($this->iNumChannels * $this->iBitsPerSample) >> 3;
            $aHeader['iChunkSize']     = $iFileSize - 8;
            $aHeader['iSubChunk2Size'] = $iFileSize - self::HEADER_SIZE;
            $aHeader['iNumChannels']   = $this->iNumChannels;
            $aHeader['iSampleRate']    = $this->iSampleRate;
            $aHeader['iByteRate']      = $this->iSampleRate * $iBlockAlign;
            $aHeader['iBlockAlign']    = $iBlockAlign;
            $aHeader['iBitsPerSample'] = $this->iBitsPerSample;
            rewind($this->rOutput);
            fwrite(
                $this->rOutput,
                pack(self::S_HEADER_PACK, ...array_values($aHeader))
            );
        }
    }
}

