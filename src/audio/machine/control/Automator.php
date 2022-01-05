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

namespace ABadCafe\PDE\Audio\Machine\Control;
use ABadCafe\PDE\Audio;

use function \max, \min;

/**
 * Automator
 *
 * Created from an IMachine instance, creates the necessary mapping and state tracking for any automatable
 * controls. This is then used to implement ISequenceControllable for the machine as a delegate.
 *
 * TODO - Allow redefinition of control curves.
 */
class Automator implements IAutomatable {

    /** @var array<int, Audio\IControlCurve> $aControlCurves */
    private array $aControlCurves = [];

    /** @var array<int, array<int, int>> $aPerVoiceControllerValues */
    private array $aPerVoiceControllerValues = [];

    /** @var array<int, callable> $aKnobCallbacks */
    private array $aKnobCallbacks = [];

    /** @var array<int, callable> $aSwitchCallbacks */
    private array $aSwitchCallbacks = [];

    /**
     * @param Audio\IMachine $oMachine
     */
    public function __construct(Audio\IMachine $oMachine) {
        $aControlDefinitions = $oMachine->getControllerDefs();

        echo "Configuring control automation for ", get_class($oMachine), "\n";

        // For each TYPE_KNOB controller, initialise a control curve that will map the controller range
        // to the expected input range for that controller.
        foreach ($aControlDefinitions as $oControlDefinition) {
            if ($oControlDefinition instanceof Knob) {
                $this->aKnobCallbacks[
                    $oControlDefinition->iControllerNumber
                ] = $oControlDefinition->cApplicator;

                // Create the control curve
                $this->aControlCurves[
                    $oControlDefinition->iControllerNumber
                ] = new Audio\ControlCurve\Linear(
                    $oControlDefinition->fMinOutput,
                    $oControlDefinition->fMaxOutput,
                    (float)self::CTRL_MIN_INPUT_VALUE,
                    (float)self::CTRL_MAX_INPUT_VALUE
                );
                echo
                    "\tAssigned Controller #", $oControlDefinition->iControllerNumber,
                    " as Knob with range ", $oControlDefinition->fMinOutput,
                    " to ", $oControlDefinition->fMaxOutput, "\n";

            } else if ($oControlDefinition instanceof Switcher) {
                $this->aSwitchCallbacks[
                    $oControlDefinition->iControllerNumber
                ] = $oControlDefinition->cApplicator;
                echo
                    "\tAssigned Controller #", $oControlDefinition->iControllerNumber,
                    " as Switch\n";
            } else {
                throw new \TypeError();
            }
        }

        $iNumVoices = $oMachine->getNumVoices();
        for ($iVoice = 0; $iVoice < $iNumVoices; ++$iVoice) {
            $this->aPerVoiceControllerValues[$iVoice] = [];
            foreach ($aControlDefinitions as $oControlDefinition) {
                $this->aPerVoiceControllerValues[$iVoice][
                    $oControlDefinition->iControllerNumber
                ] = $oControlDefinition->iInitial;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function setVoiceControllerValue(int $iVoiceNumber, int $iController, int $iValue): self {
        // Clamp the value
        $iValue = min(max($iValue, self::CTRL_MIN_INPUT_VALUE), self::CTRL_MAX_INPUT_VALUE);

        echo __METHOD__, " (", $iVoiceNumber, ", ", $iController, ", ", $iValue, ")\n";

        // If the value is different, adjust the corresponding control
        if ($iValue !== $this->aPerVoiceControllerValues[$iVoiceNumber][$iController]) {
            $this->aPerVoiceControllerValues[$iVoiceNumber][$iController] = $iValue;

            if (isset($this->aKnobCallbacks[$iController])) {
                // Knob controls need the input value mapping to some controller specific range...
                $cKnob = $this->aKnobCallbacks[$iController];
                $cKnob($iVoiceNumber, $this->aControlCurves[$iController]->map((float)$iValue));
            } else if (isset($this->aSwitchCallbacks[$iController])) {
                // Switch controls can accept the input value directly
                $cSwitch = $this->aSwitchCallbacks[$iController];
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
