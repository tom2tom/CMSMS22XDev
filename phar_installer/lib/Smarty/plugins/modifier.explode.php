<?php
/**
 * Smarty plugin
 *
 * @package    Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty explode modifier plugin
 * Type:     modifier
 * Name:     explode
 * Purpose:  split a string by a string
 *
 * @param string      $separator
 * @param string|null $string Default ''
 * @param int|null    $limit Default null
 *
 * @return array
 */
function smarty_modifier_explode($separator, ?string $string, ?int $limit = null)
{
    // provide $string default to prevent deprecation errors in PHP >=8.1
    return explode($separator, $string ?? '', $limit ?? PHP_INT_MAX);
}
