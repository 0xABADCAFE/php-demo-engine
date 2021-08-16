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

use ABadCafe\PDE\Graphics;

/**
 * TResourceLoader
 *
 * Common implementation for IResourceLoader
 */
trait TResourceLoader {

    /**
     * Sets the base path expectation for resource loads
     */
    public function setBasePath(string $sBasePath) : self {
        $this->sBasePath = $sBasePath;
        return $this;
    }

    /**
     * Load a file.
     *
     * @param  string $sRelativePath
     * @return string
     * @throws \Exception
     */
    private function loadFile(string $sRelativePath) : string {
        $sPath = $this->sBasePath . $sRelativePath;
        if (\file_exists($sPath) && \is_readable($sPath)) {
            return \file_get_contents($sPath);
        }
        throw new \Exception($sPath . ' could not be read');
    }

    /**
     * Load a PNM image file, which is the most basic image format there is.
     * Supports both P5 (greyscale ) and P6 (RGB) formats. In either case
     * an RGB format image is returned. An exception is thrown if the file
     * can't be parsed.
     *
     * @param  string $sPath
     * @return Graphics\Image;
     * @throws \Exception
     */
    protected function loadPNM(string $sPath) : Graphics\Image {
        $sRaw = $this->loadFile($sPath);
        $bRGB = (substr($sRaw, 0, 2) === 'P6');
        if (preg_match('/^(\d+)\s+(\d+)$/m', $sRaw, $aMatches)) {
            $iWidth       = (int)$aMatches[1];
            $iHeight      = (int)$aMatches[2];
            $oImage       = new Graphics\Image($iWidth, $iHeight);
            $iArea        = $iWidth * $iHeight;
            $sData        = \substr($sRaw, ($iArea * -($bRGB ? 3 : 1)));
            $iDataOffset  = 0;
            $oPixels      = $oImage->getPixels();
            if ($bRGB) {
                for ($i = 0; $i < $iArea; ++$i) {
                    $oPixels[$i] =
                        (\ord($sData[$iDataOffset++]) << 16) |
                        (\ord($sData[$iDataOffset++]) << 8) |
                        (\ord($sData[$iDataOffset++]));
                }
            } else {
                for ($i = 0; $i < $iArea; ++$i) {
                    $iGrey = \ord($sData[$iDataOffset++]);
                    $oPixels[$i] = ($iGrey << 24) | ($iGrey << 8) | ($iGrey);
                }
            }
            return $oImage;
        } else {
            throw new \Exception('Invalid PNM Format');
        }
    }
}
