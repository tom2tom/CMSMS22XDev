<?php
#Plugin handler: share_data
#(c) 2004 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

// historically, this plugin has been specially handled
// (triggered by its name smarty_cms_function...)
// to ensure that it's never cached
function smarty_cms_function_share_data($params,$template)
{
    $dest = trim(strtolower(get_parameter_value($params,'scope','parent')));
    $vars = (isset($params['data']))?$params['data']:
      ((isset($params['vars']))?$params['vars']:'');
    if( !$vars ) return ''; // nothing to do.

    if( is_string($vars) ) {
        $t_list = explode(',',$vars);
        $t_list_2 = array();
        foreach( $t_list as $one ) {
            $one = trim($one);
            if( $one ) $t_list_2[] = $one;
        }
        $vars = $t_list_2;
    }

    if( !count($vars) ) return '';

    $scope = null; // no object
    $fn = 'assign';
    switch( $dest ) {
    case 'global':
        $scope = $template->smarty;
        $fn = 'assignGlobal';
        break;

    default: // parent scope
        $scope = $template->parent;
        if( !is_object($scope) ) return '';
        if( $scope == $template->smarty ) {
            // a bit of a trick... if our parent is the global smarty object
            // we assume we want this variable available through the rest of the templates
            // so we assign it as a global.
            $fn = 'assignGlobal';
        }
        break;
    }

    foreach( $vars as $one ) {
        $var = $template->getTemplateVars($one);
        if( !is_a($var,'Smarty_Undefined_Variable') ) $scope->$fn($one,$var);
    }
}

#
# EOF
#
?>
