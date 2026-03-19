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

use FFI;
use Exception;

class ALSA implements Audio\IPCMOutput {
    private ?FFI $oFFI = null;
    private $rPCMHandle = null;

    private const BUFFER_MICROS = 25000;
    private const SND_PCM_FORMAT_S16_LE  = 2;
    private const SND_PCM_ACCESS_RW_INTERLEAVED = 3;

    public function open(): void {
        // Define only the subset of ALSA we need
        $this->oFFI = FFI::cdef("
            typedef struct _snd_pcm snd_pcm_t;
            int snd_pcm_open(snd_pcm_t **pcm, const char *name, int stream, int mode);
            int snd_pcm_set_params(snd_pcm_t *pcm, int format, int access, int iChannelCount, int rate, int soft_resample, int latency);
            long snd_pcm_writei(snd_pcm_t *pcm, const void *buffer, unsigned long size);
            int snd_pcm_recover(snd_pcm_t *pcm, int err, int silent);
            int snd_pcm_close(snd_pcm_t *pcm);
        ", "libasound.so.2");

        $rPCMHandle = $this->oFFI->new("snd_pcm_t*");

        // 0 = Playback, 0 = Standard mode
        $res = $this->oFFI->snd_pcm_open(FFI::addr($rPCMHandle), "default", 0, 0);
        if ($res < 0) {
            throw new Exception("Failed to open ALSA device.");
        }
        $this->rPCMHandle = $rPCMHandle;

        $iResult = $this->oFFI->snd_pcm_set_params(
            $this->rPCMHandle,
            self::SND_PCM_FORMAT_S16_LE,
            self::SND_PCM_ACCESS_RW_INTERLEAVED,
            1,
            Audio\IConfig::PROCESS_RATE,
            1,
            self::BUFFER_MICROS
        );
        if ($iResult < 0) {
            throw new Exception("Failed to set ALSA parameters.");
        }
    }

    public function write(Audio\Signal\Packet $oPacket): void {
        if (!$this->rPCMHandle) return;

        $aSamples = [];
        foreach ($oPacket as $sample) {
            $aSamples[] = (int)max(self::MIN_LEVEL, min(self::MAX_LEVEL, $sample * self::SCALE));
        }


        $binaryData = pack('v*', ...$aSamples);

        // Write to hardware. snd_pcm_writei expects number of *frames* (samples per channel)
        $iWritten = $this->oFFI->snd_pcm_writei($this->rPCMHandle, $binaryData, Audio\IConfig::PACKET_SIZE);

        // Handle Underrun
        if ($iWritten < 0) {
            $this->oFFI->snd_pcm_recover($this->rPCMHandle, $iWritten, 0);
        }
    }

    public function close(): void {
        if ($this->rPCMHandle) {
            $this->oFFI->snd_pcm_close($this->rPCMHandle);
            $this->rPCMHandle = null;
        }
    }
}
