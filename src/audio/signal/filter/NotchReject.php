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

/**
 * Notch reject filter.
 *
 * @see https://github.com/0xABADCAFE/random-proto-synth
 *
 * Filter implementation based on http://www.musicdsp.org/en/latest/Filters/141-karlsen.html
 */
class NotchReject extends Base {

    /**
     * @inheritDoc
     */
    protected function applyFixedCutoffFixedResonance(): void {
        $oInputSamples  = $this->oInputStream->emit($this->iLastIndex);
        $oOutputSamples = $this->oLastOutputPacket;
        $fResonance     = $this->fFixedResonance * self::F_SCALE_MAX_Q;
        foreach ($oInputSamples as $i => $fInput) {
            /** @var float $fInput */
            $this->filterSample($fInput, $this->fFixedCutoff, $fResonance);
            $oOutputSamples[$i] = $fInput - $this->fPole1;
        }
    }

    /**
     * @inheritDoc
     */
    protected function applyVaryingCutoffFixedResonance(): void {
        $oInputSamples  = $this->oInputStream->emit($this->iLastIndex);
        $oOutputSamples = $this->oLastOutputPacket;
        $oCutoffValues  = $this->oCutoffControl->emit($this->iLastIndex); // @phpstan-ignore-line : false positive
        $fResonance     = $this->fFixedResonance * self::F_SCALE_MAX_Q;
        foreach ($oInputSamples as $i => $fInput) {
            /** @var float $fInput */
            $this->filterSample($fInput, $oCutoffValues[$i] * $this->fFixedCutoff, $fResonance);
            $oOutputSamples[$i] = $fInput - $this->fPole1;
        }
    }

    /**
     * @inheritDoc
     */
    protected function applyFixedCutoffVaryingResonance(): void {
        $oInputSamples    = $this->oInputStream->emit($this->iLastIndex);
        $oOutputSamples   = $this->oLastOutputPacket;
        $oResonanceValues = $this->oResonanceControl->emit($this->iLastIndex); // @phpstan-ignore-line : false positive
        $fResonance       = $this->fFixedResonance * self::F_SCALE_MAX_Q;
        foreach ($oInputSamples as $i => $fInput) {
            /** @var float $fInput */
            $this->filterSample($fInput, $this->fFixedCutoff, $fResonance * $oResonanceValues[$i]);
            $oOutputSamples[$i] = $fInput - $this->fPole1;
        }
    }

    /**
     * @inheritDoc
     */
    protected function applyVaryingCutoffVaryingResonance(): void {
        $oInputSamples    = $this->oInputStream->emit($this->iLastIndex);
        $oOutputSamples   = $this->oLastOutputPacket;
        $oCutoffValues    = $this->oCutoffControl->emit($this->iLastIndex); // @phpstan-ignore-line : false positive
        $oResonanceValues = $this->oResonanceControl->emit($this->iLastIndex); // @phpstan-ignore-line : false positive
        foreach ($oInputSamples as $i => $fInput) {
            /** @var float $fInput */
            $this->filterSample($fInput, $oCutoffValues[$i] * $this->fFixedCutoff, self::F_SCALE_MAX_Q * $oResonanceValues[$i]);
            $oOutputSamples[$i] = $fInput - $this->fPole1;
        }
    }
}
