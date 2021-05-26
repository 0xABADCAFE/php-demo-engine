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
 * Basic analogish sounding drum machine
 */
class TRNaN implements Audio\IMachine {

    const
        KICK      = 0,
        SNARE     = 1,
        HH_CLOSED = 2,
        HH_OPEN   = 3,
        COWBELL   = 4,
        CLAP      = 5
    ;

    /**
     * These voices will mute
     */
    const MUTE_GROUPS = [
        self::HH_CLOSED => [self::HH_OPEN],
        self::HH_OPEN   => [self::HH_CLOSED]
    ];

    use TPolyphonicMachine;

    private $aVoices = [];

    public function __construct() {
        $this->initPolyphony(6);
        $this->aVoices[self::KICK]      = $this->initKick();
        $this->aVoices[self::SNARE]     = $this->initSnare();
        $this->aVoices[self::HH_CLOSED] = $this->initHiHatClosed();
        $this->aVoices[self::HH_OPEN]   = $this->initHiHatOpen();
        $this->aVoices[self::COWBELL]   = $this->initCowbell();
        $this->aVoices[self::CLAP]      = $this->initClap();
        for ($i = 0; $i < $this->iNumVoices; ++$i) {
            $this->setVoiceSource($i, $this->aVoices[$i]);
        }
    }

    public function setVoiceNote(int $iVoiceNumber, string $sNoteName) : self {
        return $this;
    }

    public function startVoice(int $iVoiceNumber) : self {
        if (isset(self::MUTE_GROUPS[$iVoiceNumber])) {
            foreach (self::MUTE_GROUPS[$iVoiceNumber] as $iMuteNumber) {
                $this->aVoices[$iMuteNumber]->disable();
            }
        }
        isset($this->aVoices[$iVoiceNumber]) && $this->aVoices[$iVoiceNumber]->reset()->enable();
        return $this;
    }

    public function stopVoice(int $iVoiceNumber) : self {
        isset($this->aVoices[$iVoiceNumber]) && $this->aVoices[$iVoiceNumber]->disable();
        return $this;
    }

    /**
     * Create the initial kick drum sound. This is just a low frequency sine with a pitch and volume envelope.
     *
     * @return Audio\Signal\IStream
     */
    private function initKick() : Audio\Signal\IStream {
        $oOscillator = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Sine,
            48.0
        );

        $oPitchEnv = new Audio\Signal\Envelope\DecayPulse(
            20.0,
            0.07
        );
        $oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.9,
            0.2
        );
        $oOscillator
            ->setPitchModulator($oPitchEnv)
            ->setEnvelope($oVolumeEnv);

        $oAutoMute = new Audio\Signal\AutoMuteAfter($oOscillator, 1.5);

        return $oAutoMute->disable();
    }

    /**
     * Create the initial snare drum sound. This is a pair of triangle oscillators and a noise source mixed and
     * fed through a decay envelope controlled VCA.
     *
     * @return Audio\Signal\IStream
     */
    private function initSnare() : Audio\Signal\IStream {

        $fRatio = 349.0 / 185.0;
        $fBase  = 180.0;

        $oNoise = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise
        );

        $oOscillator1 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Square,
            $fBase
        );
        $oOscillator2 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\Triangle,
            $fBase * $fRatio
        );

        $oOscillator1->setPhaseModulator($oNoise)->setPhaseModulationIndex(0.2);
        $oOscillator2->setPhaseModulator($oNoise)->setPhaseModulationIndex(0.05);

        $oMixer = new Audio\Signal\FixedMixer();
        $oMixer
            ->addInputStream('l', $oOscillator1, 0.8)
            ->addInputStream('h', $oOscillator2, 0.6)
            ->addInputStream('n', $oNoise, 0.5);

        $oFilter = new Audio\Signal\Filter\LowPass(
            $oMixer,
            0.5,
            0.25
        );

        $oVolumeEnv = new Audio\Signal\Envelope\Shape(
            0.8,
            [
                [0.05, 0.125],
                [0.0, 0.1]
            ]
        );
        $oVCA = new Audio\Signal\Modulator($oFilter, $oVolumeEnv);

        $oAutoMute = new Audio\Signal\AutoMuteAfter($oVCA, 0.225);

        return $oAutoMute->disable();
    }

    /**
     * Create the closed high hat sound. This is a short shot of white noise passed through a resonand bandpass
     * with a decay envelope.
     *
     * @return Audio\Signal\IStream
     */
    private function initHiHatClosed() : Audio\Signal\IStream {
        $oNoise = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise
        );
        $oFilter = new Audio\Signal\Filter\BandPass(
            $oNoise,
            0.6,
            0.5
        );
        $oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.8,
            0.015
        );
        $oVCA = new Audio\Signal\Modulator($oFilter, $oVolumeEnv);
        $oAutoMute = new Audio\Signal\AutoMuteAfter($oVCA, 0.15);

        return $oAutoMute->disable();
    }

    /**
     * Create the open high hat sound. Same as the closed version, with slightly different filter settings and a longer
     * decay.
     *
     * @return Audio\Signal\IStream
     */
    private function initHiHatOpen() : Audio\Signal\IStream {
        $oNoise = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise
        );
        $oFilter = new Audio\Signal\Filter\BandPass(
            $oNoise,
            0.53,
            0.65
        );
        $oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            0.8,
            0.05
        );
        $oVCA      = new Audio\Signal\Modulator($oFilter, $oVolumeEnv);
        $oAutoMute = new Audio\Signal\AutoMuteAfter($oVCA, 0.5);

        return $oAutoMute->disable();
    }

    /**
     * Crate the cowbell sound. This is a pair of pulse generators in a detuned 5th fed through a resonant band pass.
     * The higher frequency generator accepts a small amount of phase modulation from the lower one in order to
     * increase the metallicity. A short decay envelope is used that has a hard initial drop then a longer decay.
     *
     * @return Audio\Signal\IStream
     */
    private function initCowbell() : Audio\Signal\IStream {
        $oEnvelope = new Audio\Signal\Envelope\Shape(
            0.2, [
                [0.45, 0.001],
                [0.03, 0.1],
                [0.0, 0.5]
            ]
        );

        $fRatio = 869.4 / 587.3;
        $fBase  = 580;

        $oOscillator1 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\AliasedPulse(0.43),
            $fBase
        );
        $oOscillator2 = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\AliasedPulse(0.27),
            $fBase * $fRatio
        );
        $oOscillator1->setEnvelope($oEnvelope);
        $oOscillator2
            ->setEnvelope($oEnvelope)
            ->setPhaseModulator($oOscillator1)
            ->setPhaseModulationIndex(0.065)
        ;
        $oMixer = new Audio\Signal\FixedMixer();
        $oMixer
            ->addInputStream('l', $oOscillator1, 1.0)
            ->addInputStream('h', $oOscillator2, 1.0)
            ->setOutputLevel(0.5);
        $oFilter = new Audio\Signal\Filter\BandPass(
            $oMixer,
            0.052,
            0.65
        );

        $oAutoMute = new Audio\Signal\AutoMuteAfter($oFilter, 0.6);

        return $oAutoMute->disable();
    }

    /**
     * Clap
     *
     * @return Audio\Signal\IStream
     */
    private function initClap() : Audio\Signal\IStream {
        $oNoise = new Audio\Signal\Oscillator\Sound(
            new Audio\Signal\Waveform\WhiteNoise
        );
        $oFilter = new Audio\Signal\Filter\BandPass(
            $oNoise,
            0.09,
            0.1
        );
        $oVolumeEnv = new Audio\Signal\Envelope\DecayPulse(
            1.33,
            0.03
        );
        $oVCA = new Audio\Signal\Modulator($oFilter, $oVolumeEnv);
        $oAutoMute = new Audio\Signal\AutoMuteAfter($oVCA, 0.175);

        return $oAutoMute->disable();
    }
}

