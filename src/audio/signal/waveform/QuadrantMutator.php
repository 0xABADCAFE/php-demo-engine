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

namespace ABadCafe\PDE\Audio\Signal\Waveform;
use ABadCafe\PDE\Audio;

/**
 * QuadrantMutator
 *
 * Divides a waveform into four quadrants and allows them to be rearranged.
 */
class QuadrantMutator implements Audio\Signal\IWaveform {

    const
        PERIOD           = 4.0,
        DEFAULT_MUTATION = [
            // Quadrant phase shift, Bias Adjust, Scale.
            // This default configuration rearranges a sine wave into something resembling a triangle.
            [ 3.0,  1.0, 1.0],
            [ 1.0,  1.0, 1.0],
            [-1.0, -1.0, 1.0],
            [-3.0, -1.0, 1.0]
        ];

    private Audio\Signal\IWaveform $oSource;

    /** @var float[][] $aMutate */
    private array $aMutate;
    private float $fPeriodAdjust;

    /**
     * Constructor
     *
     * @param Audio\Signal\IWaveform $oSource
     * @param float[][]|null $aMutate
     */
    public function __construct(Audio\Signal\IWaveform $oSource, ?array $aMutate = null) {
        $this->oSource       = $oSource;
        $this->aMutate       = $aMutate ?: self::DEFAULT_MUTATION;
        $this->fPeriodAdjust = $oSource->getPeriod() / self::PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function getPeriod(): float {
        return self::PERIOD;
    }

    /**
     * @inheritDoc
     */
    public function map(Audio\Signal\Packet $oInput): Audio\Signal\Packet {

        $oAdjust = clone $oInput;

        // Mutate the quadrant phase
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase      = $oInput[$i];
            $iQuadrant   = ((int)$fPhase) & 3;
            $fPhase      += $this->aMutate[$iQuadrant][0];
            $fPhase      *= $this->fPeriodAdjust;
            $oAdjust[$i] = $fPhase;
        }

        //
        $oOutput = $this->oSource->map($oAdjust);

        // Mutate the quadrant bias
        for ($i = 0; $i < Audio\IConfig::PACKET_SIZE; ++$i) {
            $fPhase      = $oInput[$i];
            $iQuadrant   = ((int)$fPhase) & 3;
            $oOutput[$i] = ($oOutput[$i] * $this->aMutate[$iQuadrant][2]) + $this->aMutate[$iQuadrant][1];
        }

        return $oOutput;
    }
}
