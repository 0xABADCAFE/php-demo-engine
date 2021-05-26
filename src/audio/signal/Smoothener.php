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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

/**
 * Smoothener
 *
 * Simple time averaging smoother for streams based on the same
 */
class Smoothener implements IStream {

    use TStream, TPacketIndexAware;

    private int     $iPosition = 0;
    private IStream $oStream;
    private Packet  $oLastPacket;

    private float
        $fPrev1 = 0.0,
        $fPrev2 = 0.0,
        $fPrev3 = 0.0,
        $fPrev4 = 0.0
    ;

    /**
     * Constructor
     *
     * @param float $fOutLevel
     */
    public function __construct(IStream $oStream) {
        self::initStreamTrait();
        $this->oLastPacket = Packet::create();
        $this->oStream = $oStream;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->iPosition;
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->iPosition  = 0;
        $this->iLastIndex = 0;
        $this->oStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;
        if (!$this->bEnabled) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oLastPacket;
        }
        return $this->emitNew();
    }

    /**
     * @return Packet
     */
    private function emitNew() : Packet {
        $oInput  = $this->oStream->emit($this->iLastIndex);
        $oOutput = $this->oLastPacket;
        // Avoid sharp transitions at the edges with a simple hamming filter.
        $fPrev1  = $this->fPrev1;
        $fPrev2  = $this->fPrev2;
        $fPrev3  = $this->fPrev3;
        $fPrev4  = $this->fPrev4;
        foreach ($oInput as $i => $fSample) {
            $oOutput[$i] = 0.1 * (
                $fSample + $fPrev4 +
                2.0 * ($fPrev1 + $fPrev3)
                + 4.0 * $fPrev2
            );
            $fPrev4 = $fPrev3;
            $fPrev3 = $fPrev2;
            $fPrev2 = $fPrev1;
            $fPrev1 = $fSample;
        }
        $this->fPrev1 = $fPrev1;
        $this->fPrev2 = $fPrev2;
        $this->fPrev3 = $fPrev3;
        $this->fPrev4 = $fPrev4;
        return $oOutput;
    }
}
