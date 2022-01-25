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

namespace ABadCafe\PDE\Audio;

use ABadCafe\PDE;

use ABadCafe\PDE\Audio\Sequence\Event;
use ABadCafe\PDE\Audio\Signal\IWaveform;
use ABadCafe\PDE\Audio\Signal\Envelope;
use ABadCafe\PDE\Audio\Machine\ProPHPet;
use ABadCafe\PDE\Audio\Machine\TRNaN;

/**
 * Player
 */
class Player implements PDE\System\IAsynchronous {

    use PDE\System\TAsynchronous;

    /**
     * Constructor
     */
    public function __construct() {
        $this->initAsyncProcess();
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->closeSocket(self::ID_PARENT);
    }

    /**
     * Start playback
     */
    public function start(): void {

    }

    /**
     * Schtop playback
     */
    public function stop(): void {

    }

    /**
     * @inheritDoc
     */
    protected function runSubprocess(): void {
//         shell_exec('aplay demos/res/bad_gear.wav >/dev/null 2>&1');
//         return;

        $oSequencer = new Machine\Sequencer();
        $oSequencer->setBeatsPerMeasure(8);
        $oSequencer->setTempo(97);

        $oDrumMachine = new Machine\TRNaN();
        $oDrumMachine->setOutputLevel(1);

        $oChipMachine = new Machine\AYeSID(3);
        $oChipMachine->setVoiceMaskEnvelope(3, new Signal\Envelope\Shape(
            0.6,
            [
                [0.0, 0.15]
            ]
        ));

        $oChipMachine->setVoiceMaskEnvelope(4, new Signal\Envelope\Shape(
            0.6,
            [
                [0.0, 0.2]
            ]
        ));


        $oChipMachine
            ->setPulseWidthLFORate(1.125)
            ->enablePulseWidthLFO()
            ->setVoiceWaveform(0, Signal\IWaveform::PULSE)
            ->setVoiceWaveform(1, Signal\IWaveform::PULSE)
            ->setVoiceWaveform(2, Signal\IWaveform::POKEY)
            ->setVoiceVibratoRate(0, 6.0)
            ->setVoiceVibratoDepth(0, 0.1)
            ->setVoiceVibratoRate(1, 6.0)
            ->setVoiceVibratoDepth(1, 0.1)
            ->setOutputLevel(0.6)
            ->setInsert(new Signal\Insert\DelayLoop(null, 152.6 * 3, 0.6))
        ;

        $oFMPad = new Machine\TwoOpFM(4);
        $oFMPad
            ->setModulatorWaveform(Signal\IWaveform::SINE_FULL_RECT)
            ->setModulatorRatio(2.01)
            ->setModulatorLevelEnvelope(
                new Signal\Envelope\Shape(
                    0.0, [
                        [1.0, 3.0],
                        [0.0, 10.0]
                    ]
                )
            )
            ->setModulationIndex(0.4)
            ->setModulatorMix(0.2)
            ->setCarrierWaveform(Signal\IWaveform::SINE)
            ->setCarrierRatio(1.999)
            ->setCarrierLevelEnvelope(
                new Signal\Envelope\Shape(
                    0.0, [
                        [1.0, 1.00],
                        [0.0, 12.0]
                    ]
                )
            )
            ->setCarrierMix(0.2)
            ->setPitchLFODepth(0.03)
            ->setPitchLFORate(4.5)
            ->enablePitchLFO(true, true)
            ->setOutputLevel(0.175)
        ;


        $oElectricGuitar = new ProPHPet(4);
        $oElectricGuitar
            // Oscillator 1 config
            ->assignEnumeratedWaveform(IWaveform::SINE_SAW, ProPHPet::TARGET_OSC_1)
            ->setFrequencyRatio(1.00, ProPHPet::TARGET_OSC_1)
            ->setLevel(0.25, ProPHPet::TARGET_OSC_1)
            ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 2.5 * 3), ProPHPet::TARGET_OSC_1)

            // Oscillator 2 config
            ->assignEnumeratedWaveform(IWaveform::SQUARE, ProPHPet::TARGET_OSC_2)
            ->setFrequencyRatio(1.995, ProPHPet::TARGET_OSC_2)
            ->setLevel(1.0,  ProPHPet::TARGET_OSC_2)
            ->assignLevelEnvelope(new Envelope\DecayPulse(1.0, 0.7 * 3), ProPHPet::TARGET_OSC_2)

            // Modulation
            ->setPhaseModulationIndex(0.3)

            // Filter - Auto wah
            ->setFilterMode(ProPHPet::FILTER_LOWPASS)
            ->setFilterCutoff(0.52)
            ->setFilterResonance(0.1)

            // LFO Config
            ->setLevel(0.05, ProPHPet::TARGET_PITCH_LFO)
            ->setLFORate(4.5, ProPHPet::TARGET_PITCH_LFO)
            ->enablePitchLFO(ProPHPet::TARGET_OSC_1)
            ->enablePitchLFO(ProPHPet::TARGET_OSC_2)
            ->setLevel(0.2, ProPHPet::TARGET_CUTOFF_LFO)
            ->setLFORate(0.25 * 97/60, ProPHPet::TARGET_CUTOFF_LFO)
            ->enableCutoffLFO()
            // Output
            ->setOutputLevel(0.15)
            ->setInsert(new Signal\Insert\DelayLoop(null, 150*4, 0.3))
        ;


        $oPerc = new TRNaN;
        $oPerc->setOutputLevel(1.25);

        $oBassLine = new Machine\TBNaN();
        $oBassLine->setEnumeratedWaveform(Signal\IWaveform::PULSE);
        $oBassLine->setResonance(0.2);
        $oBassLine->setCutoff(0.20);
        $oBassLine->setOutputLevel(0.75);

        $oSequencer
            ->addMachine('sub', $oElectricGuitar)
            ->addMachine('perc', $oPerc)
            ->addMachine('bass', $oBassLine)
            ->addMachine('chip', $oChipMachine)
            ->addMachine('pad',  $oFMPad)
        ;

        $oSequencer->allocatePattern('perc', [0])
            ->addEvent(Event::noteOn('B3', 80), Machine\TRNaN::KICK, 27)
            ->addEvent(Event::noteOn('B3', 100), Machine\TRNaN::KICK, 28)
            ->addEvent(Event::noteOn('A3', 100), Machine\TRNaN::SNARE, 28)
            ->addEvent(Event::noteOn('A3', 100), Machine\TRNaN::SNARE, 30)
            ->addEvent(Event::noteOn('A4', 75),  Machine\TRNaN::HH_OPEN, 27)
            ->addEvent(Event::noteOn('A4', 75),  Machine\TRNaN::HH_OPEN, 31)
        ;

        $oSequencer->allocatePattern('perc', [1, 2])
            ->addEvent(Event::noteOn('B3', 100), Machine\TRNaN::KICK, 0, 4)
            ->addEvent(Event::noteOn('A3', 80), Machine\TRNaN::KICK, 9)
            ->addEvent(Event::noteOn('A3', 80), Machine\TRNaN::KICK, 27)
            ->addEvent(Event::noteOn('A3', 100), Machine\TRNaN::SNARE, 4, 8)
            ->addEvent(Event::noteOn('A3', 100), Machine\TRNaN::SNARE, 30)
            ->addEvent(Event::noteOn('A4', 55),  Machine\TRNaN::HH_CLOSED, 0, 2)
            ->addEvent(Event::noteOn('A4', 55),  Machine\TRNaN::HH_OPEN, 31)

            ->addEvent(Event::noteOn('C#4', 100), Machine\TRNaN::COWBELL, 11)
        ;

        $oSequencer->allocatePattern('perc', [3]);


        $oSequencer->allocatePattern('pad', [1])
            ->addEvent(Event::noteOn('D2', 50), 0, 0)
            ->addEvent(Event::noteOn('A2', 30), 1, 0)
            ->addEvent(Event::noteOn('A3', 30), 2, 0)
        ;

        $oSequencer->allocatePattern('pad', [2])
            ->addEvent(Event::noteOn('D4', 40), 3, 0)
        ;

        $oSequencer->allocatePattern('chip', [0])
            ->addEvent(Event::noteOn('A#4', 80), 1, 28)
            ->addEvent(Event::noteOn('G4', 80), 0, 29)
            ->addEvent(Event::noteOn('A4', 80), 1, 30)
            ->addEvent(Event::noteOn('A#4', 80), 0, 31)
        ;

        $oSequencer->allocatePattern('chip', [1, 2])
            ->addEvent(Event::noteOn('D2', 10), 2, 2, 4)
            ->addEvent(Event::noteOn('A4', 80), 0, 0)
            ->addEvent(Event::noteOn('D4', 80), 1, 1)
            ->addEvent(Event::noteOn('D4', 80), 0, 3)

            ->addEvent(Event::noteOn('A4', 80), 1, 4)
            ->addEvent(Event::noteOn('A#4', 80), 0, 5)
            ->addEvent(Event::noteOn('A4', 80), 1, 6)
            ->addEvent(Event::noteOn('G4', 80), 0, 7)

            ->addEvent(Event::noteOn('A4', 80), 1, 8)
            ->addEvent(Event::noteOn('D4', 80), 0, 9)

            ->addEvent(Event::noteOn('A#4', 80), 1, 12)
            ->addEvent(Event::noteOn('G4', 80), 0, 13)
            ->addEvent(Event::noteOn('A4', 80), 1, 14)
            ->addEvent(Event::noteOn('A#4', 80), 0, 15)

            ->addEvent(Event::noteOn('A4', 80), 0, 16)
            ->addEvent(Event::noteOn('D4', 80), 1, 17)
            ->addEvent(Event::noteOn('D4', 80), 0, 19)

            ->addEvent(Event::noteOn('A4', 80), 1, 20)
            ->addEvent(Event::noteOn('A#4', 80), 0, 21)
            ->addEvent(Event::noteOn('A4', 80), 1, 22)
            ->addEvent(Event::noteOn('G4', 80), 0, 23)

            ->addEvent(Event::noteOn('A4', 80), 1, 24)
            ->addEvent(Event::noteOn('D4', 80), 0, 25)

            ->addEvent(Event::noteOn('A#4', 80), 1, 28)
            ->addEvent(Event::noteOn('G4', 80), 0, 29)
            ->addEvent(Event::noteOn('A4', 80), 1, 30)
            ->addEvent(Event::noteOn('A#4', 80), 0, 31)
        ;

        $oSequencer->allocatePattern('bass', [1, 2])
            ->addEvent(Event::noteOn('D1', 100), 0, 1, 16)
            ->addEvent(Event::noteOn('G1', 100), 0, 3, 16)
            ->addEvent(Event::noteOn('A1', 100), 0, 5, 16)
            ->addEvent(Event::noteOn('D1', 100), 0, 7, 16)
            ->addEvent(Event::noteOn('D1', 100), 0, 9, 16)
            ->addEvent(Event::noteOn('D1', 100), 0, 11, 16)
            ->addEvent(Event::noteOn('D1', 100), 0, 13, 16)
            ->addEvent(Event::noteOn('D1', 100), 0, 15, 16)
        ;

        $oSequencer->allocatePattern('sub', [0])

            ->addEvent(Event::noteOn('F1', 100), 0, 28)
            ->addEvent(Event::noteOn('F2', 100), 1, 28)
            ->addEvent(Event::noteOn('C2', 50), 2, 28)

            ->addEvent(Event::noteOn('D1', 100), 0, 30)
            ->addEvent(Event::noteOn('D2', 100), 1, 30)
            ->addEvent(Event::noteOn('A2', 50), 2, 31)
            ->addEvent(Event::noteOn('D3', 70), 3, 31)
        ;

        $oSequencer->allocatePattern('sub', [2])

            ->addEvent(Event::noteOn('D2', 100), 0, 15)
            ->addEvent(Event::noteOn('D3', 100), 1, 16)
            ->addEvent(Event::noteOn('A2', 50), 2, 17)

            ->addEvent(Event::noteOn('G1', 100), 0, 26)
            ->addEvent(Event::noteOn('D2', 100), 1, 26)
            ->addEvent(Event::noteOn('G2', 50), 2, 26)
            ->addEvent(Event::noteOn('D3', 60), 3, 26)


            ->addEvent(Event::noteOn('F1', 100), 0, 28)
            ->addEvent(Event::noteOn('F2', 100), 1, 28)
            ->addEvent(Event::noteOn('C2', 50), 2, 28)

            ->addEvent(Event::setNote('D1'), 0, 30)
            ->addEvent(Event::setNote('D2'), 1, 30)
            ->addEvent(Event::setNote('A2'), 2, 31)

        ;

        $oOutput = Output\Piped::create();
        $oOutput->open();

        $oSequencer->playSequence(
            $oOutput,
            6.0,
            0,
            0,
            28
        );

        $oOutput->close();

    }

}
