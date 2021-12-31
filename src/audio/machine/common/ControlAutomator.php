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

use function \max, \min;

/**
 * ControlAutomator
 *
 * Created from an IMachine instance, creates the necessary mapping and state tracking for any automatable
 * controls. This is then used to implement ISequenceControllable for the machine as a delegate.
 */
class ControlAutomator implements ISequenceControllable {

    /** @var array<int, Audio\IControlCurve> $aControlCurves */
    private array $aControlCurves = [];

    /** @var array<int, array<int, int>> $aPerVoiceControllerValues */
    private array $aPerVoiceControllerValues = [];

    /** @var array<int, callable> $aKnobs */
    private array $aKnobs = [];

    /** @var array<int, callable> $aSwitches */
    private array $aSwitches = [];

    /**
     * @param Audio\IMachine $oMachine
     */
    public function __construct(Audio\IMachine $oMachine) {
        $aControllers   = $oMachine->getControllerDefs();

        // For each TYPE_KNOB controller, initialise a control curve that will map the controller range
        // to the expected input range for that controller.
        foreach ($aControllers as $iControlNumber => $oControlInfo) {
            if (ISequenceControllable::CTRL_TYPE_KNOB === $oControlInfo->iType) {
                $this->aKnobs[$iControlNumber] = $oControlInfo->cApply;
                $this->aControlCurves[$iControlNumber] = new Audio\ControlCurve\Linear(
                    $oControlInfo->fMin,
                    $oControlInfo->fMax,
                    (float)ISequenceControllable::CTRL_MIN_INPUT_VALUE,
                    (float)ISequenceControllable::CTRL_MAX_INPUT_VALUE
                );
            } else {
                $this->aSwitches[$iControlNumber] = $oControlInfo->cApply;
            }
        }

        $iNumVoices = $oMachine->getNumVoices();
        for ($iVoice = 0; $iVoice < $iNumVoices; ++$iVoice) {
            $this->aPerVoiceControllerValues[$iVoice] = [];
            foreach ($aControllers as $iControlNumber => $oControlInfo) {
                $this->aPerVoiceControllerValues[$iVoice][$iControlNumber] = $oControlInfo->iInit ?? 0;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function setVoiceControllerValue(int $iVoiceNumber, int $iController, int $iValue): self {
        // Clamp the value
        $iValue = min(max($iValue, self::CTRL_MIN_INPUT_VALUE), self::CTRL_MAX_INPUT_VALUE);

        // If the value is different, adjust the corresponding control
        if ($iValue !== $this->aPerVoiceControllerValues[$iVoiceNumber][$iController]) {
            $this->aPerVoiceControllerValues[$iVoiceNumber][$iController] = $iValue;

            if (isset($this->aKnobs[$iController])) {
                // Knob controls need the input value mapping to some controller specific range...
                $cKnob = $this->aKnobs[$iController];
                $cKnob($iVoiceNumber, $this->aControlCurves[$iController]->map((float)$iValue));
            } else if (isset($this->aSwitches[$iController])) {
                // Switch controls can accept the input value directly
                $cSwitch = $this->aSwitches[$iController];
                $cSwitch($iVoiceNumber, $iValue);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function adjustVoiceControllerValue(int $iVoiceNumber, int $iController, int $iDelta): self {
        $iDelta = min(max($iDelta, self::CTRL_MIN_INPUT_DELTA), self::CTRL_MAX_INPUT_DELTA);
        $iValue = $this->aPerVoiceControllerValues[$iVoiceNumber][$iController] + $iDelta;
        return $this->setVoiceControllerValue($iVoiceNumber, $iController, $iValue);
    }

    /**
     * @inheritDoc
     *
     * Dummy. We don't implement this.
     */
    public function getControllerDefs(): array {
        return [];
    }

}
