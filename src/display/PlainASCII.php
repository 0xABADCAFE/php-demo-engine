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

    const DEFAULT_PARAMETERS = [

        /**
         * Default background colour index, 0-255
         */
        'iBGColour' => self::BLACK,

        /**
         * Default foreground colour index, 0-255
         */
        'iFGColour' => self::WHITE,

        /**
         * Characters used to represent luminance, in increasing order of pixel coverage
         */
        'sLumaChars' => self::DEF_LUMA_CHAR
    ];

    use TASCIIArt, TInstrumented;

    /**
     * @var string[] $aBlockMapReplace
     *
     * These arrays are used to convert any ICustomChars characters just before display.
     */
    private static array $aBlockMapReplace = [];

    /**
     * @inheritDoc
     */
    public function __construct(int $iWidth, int $iHeight) {
        parent::__construct($iWidth, $iHeight);
        $this->initFixedColours();
        $this->initASCIIBuffer($iWidth, $iHeight);
        if (empty(self::$aBlockMapReplace)) {
            self::$aBlockMapReplace = array_combine(
                array_map('chr', array_keys(ICustomChars::MAP)),
                array_values(ICustomChars::MAP)
            );
        }
        $this->reset();
    }

    public function __destruct() {
        echo IANSIControl::ATTR_RESET . IANSIControl::CRSR_ON;
        $this->reportRedraw();
    }

    /**
     * @inheritDoc
     */
    public function clear(): self {
        $this->resetASCIIBuffer();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function redraw(): self {
        $this->beginRedraw();

        $sRawBuffer = '';
        $sLength    = strlen($this->sRawBuffer);
        // We can't use str_replace() here due to subsequent search terms matching previous replace terms.
        // Also, this iterates once.
        for ($i = 0; $i < $sLength; ++$i) {
            $sCharacter = $this->sRawBuffer[$i];
            $sRawBuffer .= self::$aBlockMapReplace[$sCharacter] ?? $sCharacter;
        }

        echo IANSIControl::CRSR_TOP_LEFT . $this->sFGColour . $this->sBGColour . $sRawBuffer;
        $this->endRedraw();
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setParameters(array $aParameters): self {
        $oParameters = $this->filterRawParameters($aParameters);
        if (isset($oParameters->iFGColour)) {
            $this->setForegroundColour($oParameters->iFGColour);
        }
        if (isset($oParameters->iBGColour)) {
            $this->setBackgroundColour($oParameters->iBGColour);
        }
        if (isset($oParameters->sLumaChars)) {
            $this->setLuminanceCharacters(urldecode($oParameters->sLumaChars));
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function waitForFrame(): self {
        // If we are leaving this display, make sure we reset anything we messed with.
        echo IANSIControl::ATTR_RESET;
        return $this;
    }
}
