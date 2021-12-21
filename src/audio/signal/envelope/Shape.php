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

namespace ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio;
use function \count, \end, \is_array, \max, \min;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

class Shape extends Base {

    use Audio\Signal\TPacketIndexAware, Audio\Signal\TStream;

    const
        MIN_TIME = 0.0001,
        MAX_TIME = 100.0
    ;

    /** @var float[2][] $aPoints */
    private array $aPoints = [
        0 => [0, 0]
    ];

    private Audio\Signal\Packet
        $oOutputPacket, // Buffer for control signal
        $oFinalPacket   // Fixed packet filled with the final envelope value
    ;

    private array
        /** @var {int, float}[] $aProcessPoints : Envelope points, converted into Sample Position => Level pairs */
        $aProcessPoints  = [],

        /** @var int[] $aProcessPoints : Indexes to the Process Points array, keyed by the Sample Position they start at  */
        $aProcessIndexes = []
    ;

    private int $iLastPosition   = 0;    // Used to early out and return the fixed packet

    private float
        $fGradient = 0, // Current Interpolant Gradient
        $fYOffset  = 0  //  Current Interpolant Y Offset
    ;

    private int $iXOffset = 0; //  Current Interpolant X Offset

    /**
     * Constructor. Accepts an initial output level and an optional array of level/time pairs
     *
     * @param float      $fInitial
     * @param float[2][] $aPoints  - Array of level/time pairs
     *
     */
    public function __construct(float $fInitial = 0, array $aPoints = []) {
        self::initStreamTrait();
        $this->oOutputPacket = Audio\Signal\Packet::create();
        $this->oFinalPacket  = Audio\Signal\Packet::create();
        $this->setShape($fInitial, $aPoints);
    }

    /**
     * Specify a new shape.
     *
     * @param float      $fInitial
     * @param float[2][] $aPoints
     */
    public function setShape(float $fInitial, array $aPoints) : self {
        $this->aPoints = [
            0 => [$fInitial, 0]
        ];
        foreach ($aPoints as $aPoint) {
            if (!\is_array($aPoint) || \count($aPoint) != 2) {
                throw new \Exception();
            }
            $this->aPoints[] = [
                (float)$aPoint[0],
                \min(\max((float)$aPoint[1], self::MIN_TIME), self::MAX_TIME)
            ];
        }
        $this->bParameterChanged = true;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        if (!$this->bEnabled) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oOutputPacket;
        }
        if ($this->bParameterChanged) {
            $this->recalculate();
        }
        $iLength = Audio\IConfig::PACKET_SIZE;

        // If we are at the end of the envelope, just return the final packet
        if ($this->iSamplePosition >= $this->iLastPosition) {
            $this->iSamplePosition += $iLength;
            return clone $this->oFinalPacket;
        }

        for ($i = 0; $i < $iLength; ++$i) {
            // If the sample position hits a process index position, we need to recalculate our interpolants
            if (isset($this->aProcessIndexes[$this->iSamplePosition])) {
                $this->updateInterpolants();
            }
            $this->oOutputPacket[$i] = $this->fYOffset + (++$this->iSamplePosition - $this->iXOffset) * $this->fGradient;
        }
        return $this->oOutputPacket;
    }

    /**
     * Recalculate the internal process points
     */
    private function recalculate() {
        $this->aProcessPoints  = [];
        $this->aProcessIndexes = [];
        $iProcessRate = Audio\IConfig::PROCESS_RATE;
        $fTimeTotal   = 0.0;
        $i = 0;
        foreach ($this->aPoints as $aPoint) {
            $fTimeTotal += $aPoint[1] * $this->fTimeScale;
            $iPosition = (int)($fTimeTotal * $iProcessRate);
            $this->aProcessIndexes[$iPosition] = $i;
            $this->aProcessPoints[$i++] = (object)[
                'iStart' => $iPosition,
                'fLevel' => $aPoint[0] * $this->fLevelScale
            ];
        }
        $oLastPoint = \end($this->aProcessPoints);

        // Pad on the last point again with a slight time offset. This ensures the interpolant code is always acting between a pair
        // of points and avoids wandering off the end of the array.
        $this->aProcessPoints[$i] = (object)[
            'iStart' => $oLastPoint->iStart + 16,
            'fLevel' => $oLastPoint->fLevel
        ];

        $this->iLastPosition = $oLastPoint->iStart;
        $this->oFinalPacket->fillWith($oLastPoint->fLevel);

        $this->bParameterChanged = false;
    }

    /**
     * Calculate the interpolants for the current phase of the envelope
     */
    private function updateInterpolants() {
        $iIndex  = $this->aProcessIndexes[$this->iSamplePosition];
        $oPointA = $this->aProcessPoints[$iIndex];
        $oPointB = $this->aProcessPoints[$iIndex + 1];
        $this->fGradient = ($oPointB->fLevel - $oPointA->fLevel) / (float)($oPointB->iStart - $oPointA->iStart);
        $this->fYOffset  = $oPointA->fLevel;
        $this->iXOffset  = $oPointA->iStart;
    }
}
