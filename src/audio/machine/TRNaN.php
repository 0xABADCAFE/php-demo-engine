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

namespace ABadCafe\PDE\Audio\Machine;
use ABadCafe\PDE\Audio;

/**
 * TRNaN
 *
 * Basic drum machine (so far only a kick lol)
 */
class TRNaN implements Audio\IMachine {

    private Audio\Signal\Oscillator\Sound $oKick;

    public function __construct() {
        $this->initKick();
    }

    public function noteOn(string $sNoteName, int $iVelocity, int $iChannel) : self {
        $this->oKick->reset()->enable();
        return $this;
    }

    public function noteOff(int $iChannel) : self {
        $this->oKick->disable();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPosition() : int {
        return $this->oKick->getPosition();
    }

    /**
     * @inheritDoc
     */
    public function reset() : self {
        $this->oKick->reset();
        return $this;
    }

    public function emit(?int $iIndex = null) : Audio\Signal\Packet {
        return $this->oKick->emit($iIndex);
    }

    private function initKick() {
        $this->oKick = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            22.5
        );

        $oPitchEnv = new Audio\Signal\Envelope\DecayPulse(
            32.0,
            0.07
        );
        $oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.33,
            0.1
        );
        $this->oKick
            ->setPitchModulator($oPitchEnv)
            ->setEnvelope($oVolumeEnv);
    }
}

