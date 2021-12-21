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

/**
 * ICustomChars
 *
 * Defines a subset of UTF8 codepoints we would like to use as an extension to ASCII-7, using the range 128-255
 */
interface ICustomChars {

    const MAP = [
        // Box elements mapped to 0x80 - 0x9F
        0x80 => "\u{2580}", //  [▀ ▀ ▀ ▀] // Upper half
        0x81 => "\u{2581}", //  [▁ ▁ ▁ ▁] // Lower 1/8
        0x82 => "\u{2582}", //  [▂ ▂ ▂ ▂] // Lower 2/8
        0x83 => "\u{2583}", //  [▃ ▃ ▃ ▃] // Lower 3/8
        0x84 => "\u{2584}", //  [▄ ▄ ▄ ▄] // Lower 4/8
        0x85 => "\u{2585}", //  [▅ ▅ ▅ ▅] // Lower 5/8
        0x86 => "\u{2586}", //  [▆ ▆ ▆ ▆] // Lower 6/8
        0x87 => "\u{2587}", //  [▇ ▇ ▇ ▇] // Lower 7/8
        0x88 => "\u{2588}", //  [█ █ █ █] // Full
        0x89 => "\u{2589}", //  [▉ ▉ ▉ ▉] // Left 7/8
        0x8A => "\u{258A}", //  [▊ ▊ ▊ ▊] // Left 6/8
        0x8B => "\u{258B}", //  [▋ ▋ ▋ ▋] // Left 5/8
        0x8C => "\u{258C}", //  [▌ ▌ ▌ ▌] // Left 4/8
        0x8D => "\u{258D}", //  [▍ ▍ ▍ ▍] // Left 3/8
        0x8E => "\u{258E}", //  [▎ ▎ ▎ ▎] // Left 2/8
        0x8F => "\u{258F}", //  [▏ ▏ ▏ ▏] // Left 1/8
        0x90 => "\u{2590}", //  [▐ ▐ ▐ ▐] // Right half
        0x91 => "\u{2591}", //  [░ ░ ░ ░] // Light
        0x92 => "\u{2592}", //  [▒ ▒ ▒ ▒] // Medium
        0x93 => "\u{2593}", //  [▓ ▓ ▓ ▓] // Dark
        0x94 => "\u{2594}", //  [▔ ▔ ▔ ▔] // Upper 1/8
        0x95 => "\u{2595}", //  [▕ ▕ ▕ ▕] // Right 1/8
        0x96 => "\u{2596}", //  [▖ ▖ ▖ ▖]
        0x97 => "\u{2597}", //  [▗ ▗ ▗ ▗]
        0x98 => "\u{2598}", //  [▘ ▘ ▘ ▘]
        0x99 => "\u{2599}", //  [▙ ▙ ▙ ▙]
        0x9A => "\u{259A}", //  [▚ ▚ ▚ ▚]
        0x9B => "\u{259B}", //  [▛ ▛ ▛ ▛]
        0x9C => "\u{259C}", //  [▜ ▜ ▜ ▜]
        0x9D => "\u{259D}", //  [▝ ▝ ▝ ▝]
        0x9E => "\u{259E}", //  [▞ ▞ ▞ ▞]
        0x9F => "\u{259F}", //  [▟ ▟ ▟ ▟]

        // Selected Heavy box border mapped to 0xA0 - 0xAA
        0xA0 => "\u{250F}", //  [┏ ┏ ┏ ┏] Top Left
        0xA1 => "\u{2513}", //  [┓ ┓ ┓ ┓] Top Right
        0xA2 => "\u{2517}", //  [┗ ┗ ┗ ┗] Bottom Left
        0xA3 => "\u{251B}", //  [┛ ┛ ┛ ┛] Bottom Right
        0xA4 => "\u{2501}", //  [━ ━ ━ ━] Horizontal
        0xA5 => "\u{2503}", //  [┃ ┃ ┃ ┃] Vertical
        0xA6 => "\u{254B}", //  [╋ ╋ ╋ ╋] Cross Junction
        0xA7 => "\u{2523}", //  [┣ ┣ ┣ ┣] Left T Junction
        0xA8 => "\u{252B}", //  [┫ ┫ ┫ ┫] Right T Junction
        0xA9 => "\u{2533}", //  [┳ ┳ ┳ ┳] Top T Junction
        0xAA => "\u{253B}", //  [┻ ┻ ┻ ┻] Bottom T Junction
        0xAB => "\u{2578}", //  [╸ ╸ ╸ ╸] Left Horizontal
        0xAC => "\u{257A}", //  [╺ ╺ ╺ ╺] Right Horizontal
        0xAD => "\u{2579}", //  [╹ ╹ ╹ ╹] Top Vertical
        0xAE => "\u{257B}", //  [╻ ╻ ╻ ╻] Bottom Vertical

        // Selected Geometric
        0xAF => "\u{25AA}", //  [▪ ▪ ▪ ▪]
        0xB0 => "\u{25FC}", //  [◼ ◼ ◼ ◼]
        0xB1 => "\u{25A0}", //  [■ ■ ■ ■]
    ];
}
