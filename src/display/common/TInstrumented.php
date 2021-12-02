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

namespace ABadCafe\PDE\Display;
use ABadCafe\PDE;
use \SPLFixedArray;

/**
 * TInstrumented
 *
 * Frame redraw time measurement
 */
trait TInstrumented {

    private int   $iTotalRedrawCount = 0;
    private float $fRedrawMark, $fTotalRedrawTime  = 0.0;

    /**
     * @param string|null $sWho
     */
    private function reportRedraw(?string $sWho = null): void {
        // Only report the statistics if the instance was used
        if ($this->iTotalRedrawCount) {
            printf(
                "%s Total Redraw Time: %.3f seconds, %.2f ms/redraw\n",
                $sWho ?? static::class,
                $this->fTotalRedrawTime,
                1000.0 * $this->fTotalRedrawTime / $this->iTotalRedrawCount
            );
        }
    }

    /**
     * Mark the beginning of a redraw
     */
    protected function beginRedraw(): void {
        $this->fRedrawMark = microtime(true);
    }

    /**
     * Mark the end of a redraw
     */
    protected function endRedraw(): void {
        $this->fTotalRedrawTime += microtime(true) - $this->fRedrawMark;
        ++$this->iTotalRedrawCount;
    }
}
