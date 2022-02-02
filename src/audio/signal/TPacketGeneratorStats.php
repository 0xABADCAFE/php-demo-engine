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

namespace ABadCafe\PDE\Audio\Signal;

use ABadCafe\PDE\Audio;

use function \array_sum, \printf;

/**
 * TPacketGeneratorStats
 */
trait TPacketGeneratorStats {

    /** @var array<string, int> $aSilencePackets */
    private static array $aSilencePackets = [];

    /** @var array<string, int> $aPacketsReused */
    private static array $aPacketsReused  = [];

    /** @var array<string, int> $aPacketsCreated */
    private static array $aPacketsCreated = [];

    private string $sSource = '';

    public static function printPacketStats(): void {
        $iPackets = array_sum(self::$aPacketsCreated);
        printf("Packets Created: %d [%s]\n", $iPackets, self::toTime($iPackets));
        foreach (self::$aPacketsCreated as $sSource => $iCount) {
            printf("\t%5d : %s\n", $iCount, $sSource);
        }
        $iPackets = array_sum(self::$aPacketsReused);
        printf("Packets Reused: %d [%s]\n", $iPackets, self::toTime($iPackets));
        foreach (self::$aPacketsReused as $sSource => $iCount) {
            printf("\t%5d : %s\n", $iCount, $sSource);
        }
        $iPackets = array_sum(self::$aSilencePackets);
        printf("Silence Packets: %d [%s]\n", $iPackets, self::toTime($iPackets));
        foreach (self::$aSilencePackets as $sSource => $iCount) {
            printf("\t%5d : %s\n", $iCount, $sSource);
        }
    }

    private static function toTime(int $iPackets): string {
        $fSeconds = $iPackets * Audio\IConfig::PACKET_PERIOD;
        $iSeconds = (int)$fSeconds;
        $fSeconds -= $iSeconds;
        $iMinutes = (int)($iSeconds / 60);
        $iSeconds %= 60;
        $fSeconds += $iSeconds;
        return sprintf("%02dm:%02.3fs", $iMinutes, $fSeconds);
    }

    protected final function logSilence(): void {
        ++self::$aSilencePackets[$this->sSource];
    }

    protected final function logCreated(): void {
        ++self::$aPacketsCreated[$this->sSource];
    }

    protected final function logReused(): void {
        ++self::$aPacketsReused[$this->sSource];
    }

    protected final function registerPacketGenerator(): void {
        $this->sSource = static::class;
        if (!isset(self::$aSilencePackets[$this->sSource])) {
            self::$aSilencePackets[$this->sSource] = 0;
        }
        if (!isset(self::$aPacketsCreated[$this->sSource])) {
            self::$aPacketsCreated[$this->sSource] = 0;
        }
        if (!isset(self::$aPacketsReused[$this->sSource])) {
            self::$aPacketsReused[$this->sSource] = 0;
        }
    }
}
