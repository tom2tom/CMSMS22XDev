<?php
/**
 * Smarty Internal Plugin Nocache Insert
 * Compiles the {insert} tag into the cache file
 * @deprecated since 3.1.31 - instead put PHP logic in extrnal PHP script or in plugin function
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile Insert Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Nocache_Insert
{
    /**
     * Compiles code for the {insert} tag into cache file
     *
     * @param string                   $_function insert function name
     * @param array                    $_attr     array with parameter
     * @param Smarty_Internal_Template $_template template object
     * @param string                   $_script   script name to load or 'null'
     * @param string|null              $_assign   optional variable name Default null
     *
     * @return string                  compiled code
     */
    public static function compile($_function, $_attr, $_template, $_script, $_assign = null)
    {
        if (!$_script || $_script == 'null') {
            return '';
        }
        // code for script file loading
        $_output = "<?php require_once '{$_script}';\n";

        if ($_assign) {
            $_output .= "\$_smarty_tpl->assign('{$_assign}', {$_function}(" . var_export($_attr, true) .
                        ', $_smarty_tpl), true);?>';
        } else {
            $_output .= "echo {$_function}(" . var_export($_attr, true) . ', $_smarty_tpl);?>';
        }
        $_tpl = $_template;
        while ($_tpl->_isSubTpl()) {
            $_tpl = $_tpl->parent;
        }
        return "/*%%SmartyNocache:{$_tpl->compiled->nocache_hash}%%*/{$_output}/*/%%SmartyNocache:{$_tpl->compiled->nocache_hash}%%*/";
    }
}
