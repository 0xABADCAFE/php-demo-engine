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

namespace ABadCafe\PDE\Routine;

use ABadCafe\PDE;

/**
 * Mimics a tape loader
 */
class TapeLoader extends Base {

    const DEFAULT_PARAMETERS = [
        'sSyncRGB1' => 'D72222',
        'sSyncRGB2' => '22D7D7',
        'sLoadRGB1' => '2222D7',
        'sLoadRGB2' => 'D7D722',
        'iState'    => 0,
        'iHBorder'  => 5,
        'iVBorder'  => 10,
        'sMessage'  => "",
        'iMessageX' => 2,
        'iMessageY' => 4
    ];

    const NEED_FORMAT = PDE\Display\IPixelled::FORMAT_RGB_ASCII_RGB;

    const
        STATE_IDLE = 0,
        STATE_SYNC = 1,
        STATE_LOAD = 2
    ;

    private int
        $iSyncRGB1  = 0x00D72222,
        $iSyncRGB2  = 0x0022D7D7,
        $iLoadRGB1  = 0x002222D7,
        $iLoadRGB2  = 0x00D7D722,
        $iLastRGB   = 0xFF000000
    ;

    private float $fLastIdle  = 0.0;

    private int $iRandBits;

    /**
     * @inheritDoc
     */
    public function setDisplay(PDE\IDisplay $oDisplay): self {
        $this->bCanRender =
            ($oDisplay instanceof PDE\Display\IPixelled) &&
            (($oDisplay->getFormat() & self::NEED_FORMAT) == self::NEED_FORMAT);
        $this->oDisplay = $oDisplay;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(int $iFrameNumber, float $fTimeIndex): self {
        switch ($this->oParameters->iState) {
            case self::STATE_SYNC:
                $this->renderSync($iFrameNumber, $fTimeIndex);
                break;
            case self::STATE_LOAD:
                $this->renderLoad($iFrameNumber, $fTimeIndex);
                break;
            default:
                $this->renderIdle($iFrameNumber, $fTimeIndex);
                break;
        }
        if (!empty($this->oParameters->sMessage)) {
            $iX = $this->oParameters->iMessageX + $this->oParameters->iVBorder;
            $iY = $this->oParameters->iMessageY + $this->oParameters->iHBorder;
            $this->oDisplay->writeTextSpan($this->oParameters->sMessage, $iX, $iY);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function parameterChange(): void {
        $this->iSyncRGB1 = (int)base_convert($this->oParameters->sSyncRGB1, 16, 10) & 0xFFFFFF;
        $this->iSyncRGB2 = (int)base_convert($this->oParameters->sSyncRGB2, 16, 10) & 0xFFFFFF;
        $this->iLoadRGB1 = (int)base_convert($this->oParameters->sLoadRGB1, 16, 10) & 0xFFFFFF;
        $this->iLoadRGB2 = (int)base_convert($this->oParameters->sLoadRGB2, 16, 10) & 0xFFFFFF;
    }

    /**
     * Animation phase for the "idle" state.
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function renderIdle(int $iFrameNumber, float $fTimeIndex): void {
        $oPixels    = $this->oDisplay->getPixels();
        $sRawBuffer = &$this->oDisplay->getCharacterBuffer();
        $iWidth     = $this->oDisplay->getWidth();
        $iSpan      = $this->oDisplay->getCharacterWidth();
        $iHeight    = $this->oDisplay->getHeight();
        $iOffset    = 0;
        $iASCIIPos  = 0;
        if ($fTimeIndex > $this->fLastIdle) {
            $this->fLastIdle = $fTimeIndex + (float)mt_rand() / (float)mt_getrandmax();
            $this->iLastRGB  = ($this->iLastRGB == $this->iSyncRGB1) ? $this->iSyncRGB2 : $this->iSyncRGB1;
        }
        $iRGB = $this->iLastRGB;
        for ($y = 0; $y < $iHeight; ++$y) {
            if ($y < $this->oParameters->iHBorder || $y >= $iHeight - $this->oParameters->iHBorder) {
                // Top and bottom
                for ($x = 0; $x < $iWidth; $x++) {
                    $oPixels[$iOffset + $x] = $iRGB;
                    $sRawBuffer[$iASCIIPos + $x] = ' ';
                }
            } else {
                // Left and right
                $iOffset2   = $iOffset + $iWidth - 1;
                $iASCIIPos2 = $iASCIIPos + $iWidth - 1;
                for ($x = 0; $x < $this->oParameters->iVBorder; $x++) {
                    $oPixels[$iOffset + $x]       = $iRGB;
                    $oPixels[$iOffset2 - $x]      = $iRGB;
                    $sRawBuffer[$iASCIIPos + $x]  = ' ';
                    $sRawBuffer[$iASCIIPos2 - $x] = ' ';
                }
            }
            $iASCIIPos += $iSpan;
            $iOffset   += $iWidth;
        }
    }

    /**
     * Animation phase for the "sync" state.
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function renderSync(int $iFrameNumber, float $fTimeIndex): void {
        $oPixels    = $this->oDisplay->getPixels();
        $sRawBuffer = &$this->oDisplay->getCharacterBuffer();
        $iWidth     = $this->oDisplay->getWidth();
        $iSpan      = $this->oDisplay->getCharacterWidth();
        $iHeight    = $this->oDisplay->getHeight();
        $iOffset    = 0;
        $iASCIIPos  = 0;

        // Lookup of block characters to handle scrolling sync effect
        static $aChars = null;
        if (null === $aChars) {
            $aChars = [
                ' ',
                chr(0x81),
                chr(0x82),
                chr(0x83),
                chr(0x84),
                chr(0x85),
                chr(0x86),
                chr(0x87),
            ];
        }
        $sChar  = $aChars[$iFrameNumber & 0x7];
        $iFlip  = ($iFrameNumber >> 3) & 1;
        for ($y = 0; $y < $iHeight; ++$y) {
            $iRGB = (($y ^ $iFlip) & 1) ?
                ($this->iSyncRGB1 | $this->iSyncRGB2 << 24) :
                ($this->iSyncRGB2 | $this->iSyncRGB1 << 24)
            ;
            if ($y < $this->oParameters->iHBorder || $y >= $iHeight - $this->oParameters->iHBorder) {
                // Top and bottom
                for ($x = 0; $x < $iWidth; $x++) {
                    $oPixels[$iOffset + $x] = $iRGB;
                    $sRawBuffer[$iASCIIPos + $x] = $sChar;
                }
            } else {
                // Left and right
                $iOffset2   = $iOffset + $iWidth - 1;
                $iASCIIPos2 = $iASCIIPos + $iWidth - 1;
                for ($x = 0; $x < $this->oParameters->iVBorder; $x++) {
                    $oPixels[$iOffset + $x]       = $iRGB;
                    $oPixels[$iOffset2 - $x]      = $iRGB;
                    $sRawBuffer[$iASCIIPos + $x]  = $sChar;
                    $sRawBuffer[$iASCIIPos2 - $x] = $sChar;
                }
            }
            $iASCIIPos += $iSpan;
            $iOffset   += $iWidth;
        }
    }

    /**
     * Animation phase for the "loading" state.
     *
     * @param int   $iFrameNumber
     * @param float $fTimeIndex
     */
    private function renderLoad(int $iFrameNumber, float $fTimeIndex): void {
        $oPixels    = $this->oDisplay->getPixels();
        $sRawBuffer = &$this->oDisplay->getCharacterBuffer();
        $iWidth     = $this->oDisplay->getWidth();
        $iSpan      = $this->oDisplay->getCharacterWidth();
        $iHeight    = $this->oDisplay->getHeight();
        $iOffset    = 0;
        $iASCIIPos  = 0;
        $iRGB2      = $this->iLoadRGB1 | ($this->iLoadRGB2 << 24);
        $aChars     = [
            chr(0x80),
            chr(0x84)
        ];

        // Generate a random 64-bit integer. We will switch between the upper and lower half block based on each
        // successove bit. The range of mt_rand is only 31 bits, so we use three calls.

        // PHP 8 in JIT mode forgets this local variable immediately for some reason so assign as a member.
        $this->iRandBits = mt_rand() << 33  // Upper
                         | mt_rand()        // Lower
                         ^ mt_rand() << 16; // Gap coverage

        for ($y = 0; $y < $iHeight; ++$y) {

            // Choose the upper or lower half block based on the y'th bit (modulo 64 to avoid overflows)
            $sChar = $aChars[0 != ($this->iRandBits & (1 << ($y & 63)))];
            if ($y < $this->oParameters->iHBorder || $y >= $iHeight - $this->oParameters->iHBorder) {
                // This part handles top and bottom
                for ($x = 0; $x < $iWidth; ++$x) {
                    $oPixels[$iOffset + $x] = $iRGB2;
                    $sRawBuffer[$iASCIIPos + $x] = $sChar;
                }
            } else {
                // This part handles left and right
                $iOffset2   = $iOffset + $iWidth - 1;
                $iASCIIPos2 = $iASCIIPos + $iWidth - 1;
                for ($x = 0; $x < $this->oParameters->iVBorder; $x++) {
                    $oPixels[$iOffset + $x]       = $iRGB2;
                    $oPixels[$iOffset2 - $x]      = $iRGB2;
                    $sRawBuffer[$iASCIIPos + $x]  = $sChar;
                    $sRawBuffer[$iASCIIPos2 - $x] = $sChar;
                }
            }
            $iASCIIPos += $iSpan;
            $iOffset   += $iWidth;
        }
    }
}
