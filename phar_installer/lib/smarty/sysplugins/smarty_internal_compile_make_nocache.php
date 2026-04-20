<?php
/**
 * Smarty Internal Plugin Compile Make_Nocache
 * Compiles the {make_nocache} tag
 *
 * This tag makes a variable which normally exists only while rendering
 * a compiled template available in the cached template for use in
 * non-cached expressions. Which enables use of both cached and and nocache
 * variables in a single tag.
 * In effect it is like a specialised variant of an {assign} tag.
 * {make_nocache varname} mimics {$varname=varvalue nocache}, but only
 * if the tag is used in a [sub]template context which is cached by Smarty,
 * and if that template does not already have a non-cached variable with
 * the same name. Otherwise, the tag is ignored. Which means it can sensibly
 * be used inside a loop.
 * Or if that template does have a cached variable with the same name,
 * that variable will be replaced.
 * If the variable value contains object(s) which rely on PHP's magic
 * __set_state() static method for regeneration after export, compilation
 * will fail, as Smarty wouldn't process such method even if it exists.
 *
 * Expample:
 *  {foreach $list as $item}
 *     <li>{$item.name} {make_nocache $item}{if $current==$item.id} ACTIVE{/if}</li>
 *  {/foreach}
 *
 * @deprecated since 4.4.2 Instead use {$varname=varvalue nocache}
 *  as appropriate
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
        if ($compiler->template->caching) { // aka != Smarty::CACHING_OFF
            $output = "<?php \$_smarty_tpl->smarty->ext->_make_nocache->save(\$_smarty_tpl, {$_attr[ 'var' ]});?>\n";
            $compiler->template->compiled->has_nocache_code = true;
            $compiler->suppressNocacheProcessing = true;
            return $output;
        } else {
            return true;
        }
    }
}
