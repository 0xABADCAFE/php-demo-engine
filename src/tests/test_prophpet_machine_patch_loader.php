<?php

declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$oDefinition = json_decode(file_get_contents('machines/subtractive/first.json'));

print_r($oDefinition);

$oBass = (new Audio\Machine\Factory)->createFrom($oDefinition);
