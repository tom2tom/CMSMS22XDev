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
 * @param string|null $str Default ''
 * @param int|null    $limit Default null
 *
 * @return array
 */
function smarty_modifier_explode($separator, ?string $str = '', ?int $limit = null)
{
    $str = (string)$str;
    return ($str !== '') ? explode($separator, $str, $limit ?? PHP_INT_MAX) : array();
}
