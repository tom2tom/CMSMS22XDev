<?php

/**
 * {make_nocache} Runtime Methods save(), store()
 *
 * @package    Smarty
 * @subpackage PluginsInternal
 * @author     Uwe Tews
 */
class Smarty_Internal_Runtime_Make_Nocache
{
    /**
     * Generate (echo) nocache code to make the specified (nocache) variable
     * available in cached template (using the store() method in this class).
     * Does nothing if the template has no variable with the specified name.
     *
     * @param \Smarty_Internal_Template $tpl
     * @param string                    $var name of variable to be processed
     *
     * @throws \SmartyException if the variable value includes data which
     *   cannot be successfully re-generated in the store() method
     */
    public function save(Smarty_Internal_Template $tpl, $var)
    {
        if (isset($tpl->tpl_vars[ $var ])) {
            // get the exported variable-properties:
            // export variable and strip the result's outer-container
            // (relating to the Smarty_Variable per se)
            $export = preg_replace(
                '/^\\\\?Smarty_Variable::__set_state[(]|[)]$/',
                '',
                var_export($tpl->tpl_vars[ $var ], true));
            // check for properties which will not or cannot be
            // re-generated when the variable is stored (per store() below)
            // e.g. class-object(s) expecting to use __set_state()
            if (preg_match('/([\\\\\w]+?)::__set_state/', $export, $match)) {
                throw new SmartyException("{make_nocache \${$var}} in template '{$tpl->source->name}': variable contains '{$match[1]}' object needing method '__set_state' for regeneration");
            }
            // generate 'marked' content
            echo "/*%%SmartyNocache:{$tpl->compiled->nocache_hash}%%*/\n<?php " .
                addcslashes("\$_smarty_tpl->smarty->ext->_make_nocache->store(\$_smarty_tpl, '{$var}', ", '\\') .
                $export . ");?>\n/*/%%SmartyNocache:{$tpl->compiled->nocache_hash}%%*/\n";
        }
    }

    /**
     * Assign a cached template variable.
     * Does nothing if the template already has a nocache variable with
     * the specified name. Replaces any cached variable with that name.
     *
     * @param \Smarty_Internal_Template $tpl
     * @param string                    $var variable name
     * @param array                     $properties variable properties,
     *                                  possibly including overloaded
     *                                  props and their values
     */
    public function store(Smarty_Internal_Template $tpl, $var, $properties)
    {
        if (!isset($tpl->tpl_vars[ $var ]) || !$tpl->tpl_vars[ $var ]->nocache) {
            $newVar = new Smarty_Variable();
            unset($properties[ 'nocache' ]);
            foreach ($properties as $k => $v) {
                $newVar->$k = $v; // $k might be overloaded property, deprecated for PHP 8.2+
            }
            $tpl->tpl_vars[ $var ] = $newVar;
        }
    }
}
