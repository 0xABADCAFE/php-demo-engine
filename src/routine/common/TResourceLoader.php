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
 * TResourceLoader
 *
 * Common implementation for IResourceLoader
 */
trait TResourceLoader {

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
        if (file_exists($sPath) && is_readable($sPath)) {
            return file_get_contents($sPath);
        }
        throw new \Exception($sPath . ' could not be read');
    }
}
