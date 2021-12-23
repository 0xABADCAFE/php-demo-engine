<?php


declare(strict_types = 1);

namespace ABadCafe\PDE;

require_once '../PDE.php';

$iWidth  = 120;
$iHeight = 30;

$oDisplay = Display\Factory::get()->create('PlainASCII', $iWidth, $iHeight);
$oDisplay
    ->reset()
    ->setBackgroundColour(4)
    ->writeTextSpan("Top Left", 0, 0)
    ->writeTextSpan("Top Right", 111, 0)
    ->writeTextSpan("Bottom Left", 0, 29)
    ->writeTextSpan("Bottom Right", 108, 29)
    ->writeTextSpan("Centre", 57, 15)
    ->writeTextSpan(str_repeat("0123456789", 20), 0, 2)
    ->writeTextSpan("Off Left", -20, 15)
    ->writeTextSpan("Off Right", 120, 15)
    ->writeTextSpan("Off Top", 0, -1)
    ->writeTextSpan("Off Bottom", 0, 30)
    ->writeTextSpan("Hidden Clip Left", -7, 15)
    ->writeTextSpan("Clip Right Hidden", 120 - 10, 15)
    ->writeTextSpan("Visible Line\nHidden Line", 54, 0)
    ->redraw();

sleep(5);


$oDisplay
    ->reset()
    ->writeTextBounded(file_get_contents('text_test.php'), 0, 0)
    ->redraw()
    ->waitForFrame();
