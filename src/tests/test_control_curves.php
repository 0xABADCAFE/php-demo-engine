<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oFlat   = new Audio\ControlCurve\Flat(0.5);
$oLinearP = new Audio\ControlCurve\Linear(0.25, 0.75);
$oGammaP1 = new Audio\ControlCurve\Gamma(0.25, 0.75, 0.5);
$oGammaP2 = new Audio\ControlCurve\Gamma(0.25, 0.75, 2.0);
$oLinearN = new Audio\ControlCurve\Linear(0.75, 0.25);
$oGammaN1 = new Audio\ControlCurve\Gamma(0.75, 0.25, 0.5);
$oGammaN2 = new Audio\ControlCurve\Gamma(0.75, 0.25, 2.0);

for ($i = 0; $i < 128; ++$i) {

    \printf(
        "\t%3d | %0.4f | %0.4f | %0.4f | %0.4f | %0.4f | %0.4f | %0.4f \n",
        $i,
        $oFlat->map((float)$i),
        $oLinearP->map((float)$i),
        $oGammaP1->map((float)$i),
        $oGammaP2->map((float)$i),
        $oLinearN->map((float)$i),
        $oGammaN1->map((float)$i),
        $oGammaN2->map((float)$i)
    );
}
