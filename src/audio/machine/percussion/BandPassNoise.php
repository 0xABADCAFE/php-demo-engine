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

namespace ABadCafe\PDE\Audio\Machine\Percussion;
use ABadCafe\PDE\Audio;

/**
 * BandPassNoise
 *
 * Base class for sounds that simply use bandpass filtered noise, e.g. High Hats and claps.
 */
abstract class BandPassNoise implements IVoice {

    protected Audio\Signal\Oscillator\Sound    $oNoise;
    protected Audio\Signal\IFilter             $oFilter;
    protected Audio\Signal\IEnvelope           $oVolumeEnv;

    /** @var Audio\Signal\Operator\AutoMuteSilence<Audio\Signal\Operator\Modulator> $oAutoMute */
    protected Audio\Signal\Operator\AutoMuteSilence $oAutoMute;

    /**
     * Constructor. Constructs the key component parts and defers to an abstract method to parameterise them.
     */
    public function __construct() {
        $this->oNoise  = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise()
        );
        $this->oFilter = new Audio\Signal\Filter\BandPass(
            $this->oNoise,
            0.5,
            0.0
        );
        $this->oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            1.0,
            0.05
        );
        $this->oNoise->setLevelEnvelope($this->oVolumeEnv);
        $this->oAutoMute = new Audio\Signal\Operator\AutoMuteSilence($this->oFilter, 0.03, 1/512.0);
        $this->setDefaults();
        $this->oAutoMute->disable();
    }

    /**
     * @inheritDoc
     */
    public function getOutputStream(): Audio\Signal\IStream {
        return $this->oAutoMute;
    }

    /**
     * Set the appropriate default properties.
     */
    protected abstract function setDefaults(): void;
}
