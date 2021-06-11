<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

echo "PHP Demo Engine: FM Operator Test\n";

$oModOperator1   = new Audio\Machine\FM\Operator;
$oModOperator1
    ->setRatio(1.005)
    ->setLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 1.0)
    );

$oModOperator2   = new Audio\Machine\FM\Operator;
$oModOperator2
    ->setRatio(8.01)
    ->setLevelEnvelope(
        new Audio\Signal\Envelope\DecayPulse(1.0, 0.25)
    );

$oModOperator2
    ->addModulator($oModOperator1, 0.5);

$oOutputOperator = new Audio\Machine\FM\Operator;
$oOutputOperator
    ->setOutputLevel(0.5);

$oOutputOperator
    ->addModulator($oModOperator1, 0.5)
    ->addModulator($oModOperator2, 0.25)
;

$oOutputOperator
    ->setFrequency(110.0);

$oPCMOut = Audio\Output\Piped::create();
$oPCMOut->open();

$iPackets = 3000;
while($iPackets--) {
    $oPCMOut->write($oOutputOperator->emit());
}

$oPCMOut->close();
