<?php
/**
 * This file is part of the Smarty package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/**
 * Registers some helper/polyfill functions.
 */

const SMARTY_HELPER_FUNCTIONS_LOADED = true;

/**
 * Converts the first character in $string to uppercase (A-Z) if it is an ASCII lowercase character (a-z).
 *
 * @param $string
 *
 * @return string
 */
function smarty_ucfirst_ascii($string): string
{
    if (PHP_VERSION_ID >= 80200) {
        return ucfirst($string);
    }
    return smarty_strtoupper_ascii(substr($string, 0, 1)) . substr($string, 1);
}

/**
 * Converts all uppercase ASCII characters (A-Z) in $string to lowercase (a-z).
 *
 * @param $string
 *
 * @return string
 */
function smarty_strtolower_ascii($string): string
{
    if (PHP_VERSION_ID >= 80200) {
        return strtolower($string);
    }
    return strtr($string, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
}

/**
 * Converts all lowercase ASCII characters (a-z) in $string to uppercase (A-Z).
 *
 * @param $string
 *
 * @return string
 */
function smarty_strtoupper_ascii($string): string
{
    if (PHP_VERSION_ID >= 80200) {
        return strtoupper($string);
    }
    return strtr($string, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
}