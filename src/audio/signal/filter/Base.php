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

namespace ABadCafe\PDE\Audio\Signal\Filter;

use ABadCafe\PDE\Audio;

use function \max;

/**
 * Base filter class, to be extended into Low/High/Band/Notch variants.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 *
 * Filter implementation based on http://www.musicdsp.org/en/latest/Filters/141-karlsen.html
 */
abstract class Base implements Audio\Signal\IFilter {

    const
        F_SCALE_MAX_Q  = 4.0 // The original goes to 50, but it's way OTT.
    ;

    use Audio\Signal\TPacketIndexAware, Audio\Signal\TStream;

    protected Audio\Signal\Packet  $oLastOutputPacket;

    /** Main input */
    protected Audio\Signal\IStream $oInputStream;

    protected int $iPosition = 0;

    /** Optional controls for cutoff and resonance */
    protected ?Audio\Signal\IStream
        $oCutoffControl      = null,
        $oResonanceControl   = null
    ;

    /** Filter properties */
    protected float
        $fFixedCutoff,
        $fFixedResonance,
        $fPole1    = 0.0,
        $fPole2    = 0.0,
        $fPole3    = 0.0,
        $fPole4    = 0.0,
        $fFeedback = 0.0
    ;

    /** Selected filter function, depends on which parameters are fixed and varying */
    protected $cFilterFunction;

    /** Set of possible filter functions */
    protected static $aFilterFunctionNames = [
        0 => 'applyFixedCutoffFixedResonance',
        1 => 'applyVaryingCutoffFixedResonance',
        2 => 'applyFixedCutoffVaryingResonance',
        3 => 'applyVaryingCutoffVaryingResonance',
    ];

    /**
     * Constructor
     *
     * @param Signal\Audio\IStream      $oInputStream - audio source
     * @param float                     $fFixedCutoff
     * @param float                     $fFixedResonance
     * @param Audio\Signal\IStream|null $oCutoffControl
     * @param Audio\Signal\IStream|null $oResonanceControl
     */
    public function __construct(
        Audio\Signal\IStream  $oInputStream,
        float                 $fFixedCutoff      = self::DEF_CUTOFF,
        float                 $fFixedResonance   = self::DEF_RESONANCE,
        ?Audio\Signal\IStream $oCutoffControl    = null,
        ?Audio\Signal\IStream $oResonanceControl = null
    ) {
        self::initStreamTrait();
        $this->oInputStream      = $oInputStream;
        $this->oLastOutputPacket = Audio\Signal\Packet::create();
        $this->oCutoffControl    = $oCutoffControl;
        $this->oResonanceControl = $oResonanceControl;
        $this->fFixedCutoff      = $fFixedCutoff;
        $this->fFixedResonance   = $fFixedResonance;
        $this->chooseFilterFunction();
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int {
        return $this->iPosition;
    }

    /**
     * @inheritDoc
     */
    public function reset(): self {
        $this->iPosition  = 0;
        $this->iLastIndex = 0;
        $this->oLastOutputPacket->fillWith(0.0);
        $this->oCutoffControl    && $this->oCutoffControl->reset();
        $this->oResonanceControl && $this->oResonanceControl->reset();
        $this->oInputStream->reset();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function emit(?int $iIndex = null): Audio\Signal\Packet {
        if (!$this->bEnabled) {
            return $this->emitSilence();
        }
        if ($this->useLast($iIndex)) {
            return $this->oLastOutputPacket;
        }
        return $this->oLastOutputPacket = $this->emitNew();
    }

    /**
     * @inheritDoc
     */
    public function setCutoff(float $fCutoff): self {
        $this->fFixedCutoff = max($fCutoff, self::MIN_CUTOFF);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setCutoffControl(?Audio\Signal\IStream $oCutoffControl): self {
        $this->oCutoffControl = $oCutoffControl;
        $this->chooseFilterFunction();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setResonance(float $fResonance): self {
        $this->fFixedResonance = max($fResonance, self::MIN_RESONANCE);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setResonanceControl(?Audio\Signal\IStream $oResonanceControl): self {
        $this->oResonanceControl = $oResonanceControl;
        $this->chooseFilterFunction();
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function emitNew(): Audio\Signal\Packet {
        $this->iPosition += Audio\IConfig::PACKET_SIZE;
        $cFilterFunction = $this->cFilterFunction;
        $cFilterFunction();
        return $this->oLastOutputPacket;
    }

    /**
     * Based on what controllers are currently set, work out which filter function variant to use.
     */
    protected function chooseFilterFunction(): void {
        $iFunctionIndex = ($this->oCutoffControl ? 1 : 0) | ($this->oResonanceControl ? 2 : 0);
        $this->cFilterFunction = [$this, self::$aFilterFunctionNames[$iFunctionIndex]];
    }

    /**
     * Filter a single sample. Uses 2x oversampling and dynamic feedback. The sample data enters the filter poles
     * and the various sums and differences of those afterwards can be used to recover the output of interest.
     *
     * @param float $fInput
     * @param float $fCutoff
     * @param float $fResonance
     */
    protected function filterSample(float $fInput, float $fCutoff, float $fResonance): void {
        $fInputSH    = $fInput;
        $iOverSample = 2;
        $iInvCutoff  = 1.0 - $fCutoff;
        while ($iOverSample--) {
            $fPrevFeedback   = $this->fFeedback > 1.0 ? 1.0 : $this->fFeedback;
            $this->fFeedback = $this->fFeedback * 0.418 + $fResonance * $this->fPole4 * 0.582; // dynamic feedback
            $fFeedbackPhase  = $this->fFeedback * 0.36  + $fPrevFeedback * 0.64;               // feedback phase
            $fInput          = $fInputSH - $fFeedbackPhase;                                    // inverted feedback
            $this->fPole1    = $fInput * $fCutoff + $this->fPole1 * $iInvCutoff;
            if ($this->fPole1 > 1.0) {
                $this->fPole1 = 1.0;
            }
            else if ($this->fPole1 < -1.0) {
                $this->fPole1 = -1.0;
            }  // pole 1 clipping
            $this->fPole2 = $this->fPole1 * $fCutoff + $this->fPole2 * $iInvCutoff;
            $this->fPole3 = $this->fPole2 * $fCutoff + $this->fPole3 * $iInvCutoff;
            $this->fPole4 = $this->fPole3 * $fCutoff + $this->fPole4 * $iInvCutoff;
        }
    }

    /**
     * Implementor to provide.
     */
    protected abstract function applyFixedCutoffFixedResonance(): void;

    /**
     * Implementor to provide.
     */
    protected abstract function applyVaryingCutoffFixedResonance(): void;

    /**
     * Implementor to provide.
     */
    protected abstract function applyFixedCutoffVaryingResonance(): void;

    /**
     * Implementor to provide.
     */
    protected abstract function applyVaryingCutoffVaryingResonance(): void;
}
