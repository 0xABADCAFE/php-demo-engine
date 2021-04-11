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

/**
 * PlainASCII
 *
 * Absolutely basic string buffer for ASCII art only.
 */
class PlainASCII extends Base implements IASCIIArt {

    use TASCIIArt, TInstrumented;

    /**
     * @var string[] $aBlockMapSearch, $aBlockMapReplace
     *
     * These arrays are used to convert any ICustomChars characters just before display.
     */
    private static array $aBlockMapSearch = [], $aBlockMapReplace = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);
        $this->initASCIIBuffer($iWidth, $iHeight);
        if (empty(self::$aBlockMapSearch)) {
            self::$aBlockMapSearch  = array_map('chr', array_keys(ICustomChars::MAP));
            self::$aBlockMapReplace = array_values(ICustomChars::MAP);
        }
        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::CRSR_ON, "\n";
        $this->reportRedraw();
    }

    /**
     * @inheritDoc
     */
    public function clear() : self {
        $this->resetASCIIBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw() : self {
        $this->beginRedraw();
        echo IANSIControl::CRSR_TOP_LEFT .
            str_replace(
                self::$aBlockMapSearch,
                self::$aBlockMapReplace,
                $this->sRawBuffer
            );
        $this->endRedraw();
        return $this;
    }
}
