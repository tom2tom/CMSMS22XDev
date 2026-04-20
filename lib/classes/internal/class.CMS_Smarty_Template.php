<?php

class CMS_Smarty_Template extends Smarty_Internal_Template
{
    //parent-class uses, but formerly didn't declare, these class properties
    //cuz it preferred to __get() them with guaranteed pre-population
    //if not using Smarty 4.4.2+, 5+ declare here to avoid PHP deprecation notice
//  public $compiled;
//  public $compiler;

    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if( $cache_id === null || $cache_id === false || $cache_id === '' ) {
            $cache_id = Smarty_CMS::get_instance()->get_global_cacheid();
        }
        elseif( $cache_id[0] == '|' ) {
            $global_cache_id = Smarty_CMS::get_instance()->get_global_cacheid();
            $cache_id = $global_cache_id . $cache_id;
        }
        elseif( strcasecmp($cache_id,'nocache') == 0 ) {
            $cache_id = null;
        }
        // allow relative-path for file: and extends: resources
        $this->_cache['allow_relative_path'] = true;

        if( !$template ) {
            $template = $this;
        }
        // send an event before fetching...this allows us to change template stuff.
        if( CmsApp::get_instance()->is_frontend_request() ) {
            $parms = array('template'=>&$template,'cache_id'=>&$cache_id,'compile_id'=>&$compile_id);
            \CMSMS\HookManager::do_hook('Core::TemplatePreFetch', $parms);
        }
        return parent::fetch($template,$cache_id,$compile_id,$parent);
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        echo $this->fetch($template,$cache_id,$compile_id,$parent);
    }
}
