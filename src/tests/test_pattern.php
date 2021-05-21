<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oPattern = new Audio\Sequence\Pattern(4, 64);

$oPattern->addEvent(new Audio\Sequence\NoteOn(), 0, 0, 4);

