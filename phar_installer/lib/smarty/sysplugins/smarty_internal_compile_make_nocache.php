<?php
/**
 * Smarty Internal Plugin Compile Make_Nocache
 * Compiles the {make_nocache} tag
 * That tag makes a variable which normally exists only while rendering
 * a compiled template available in the cached template for use in
 * non-cached expressions.
 * Expample:
 *  {foreach $list as $item}
 *     <li>{$item.name} {make_nocache $item}{if $current==$item.id} ACTIVE{/if}</li>
 *  {/foreach}
 * The {foreach} loop is rendered while processing the compiled template,
 * but $current is a nocache variable. Normally the {if $current==$item.id}
 * would fail as $item is unknown in the cached template.
 * {make_nocache $item} makes the current $item value known in the cached template.
 * {make_nocache} is ignored when caching is disabled or the variable
 * exists as a nocache variable.
 * If the variable value contains object(s) they must implement a
 * __set_state static method. STUPID, TODO
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     Uwe Tews
 */
class Smarty_Internal_Compile_Make_Nocache extends Smarty_Internal_CompileBase
{
    /**
     * Attribute definition: Overwrites base class.
     *
     * @var array
     * @see Smarty_Internal_CompileBase
     */
    public $option_flags = array();

    /**
     * Array of names of required attribute required by tag
     *
     * @var array
     */
    public $required_attributes = array('var');

    /**
     * Shorttag attribute order defined by its names
     *
     * @var array
     */
    public $shorttag_order = array('var');

    /**
     * Compiles code for the {make_nocache} tag
     *
     * @param array                                 $args     array with attributes from parser
     * @param \Smarty_Internal_TemplateCompilerBase $compiler compiler object
     *
     * @return mixed compiled code string | true
     */
    public function compile($args, Smarty_Internal_TemplateCompilerBase $compiler)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        if ($compiler->template->caching != Smarty::CACHING_OFF) { // 0
            $output = "<?php \$_smarty_tpl->smarty->ext->_make_nocache->save(\$_smarty_tpl, {$_attr[ 'var' ]});\n?>\n";
            $compiler->template->compiled->has_nocache_code = true;
            $compiler->suppressNocacheProcessing = true;
            return $output;
        } else {
            return true;
        }
    }
}
