<?php
#Plugin handler: cms_filepicker
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

function smarty_function_cms_filepicker($params,$template)
{
    $filepicker = \cms_utils::get_filepicker_module();
    if( !$filepicker ) return '';

    $name = trim(get_parameter_value($params,'name','picker')); //default name, since 2.2.19
    if( !$name ) return '';
    $profile_name = trim(get_parameter_value($params,'profile'));
    $prefix = trim(get_parameter_value($params,'prefix'));
    $value = trim(get_parameter_value($params,'value'));
    $top = trim(get_parameter_value($params,'top'));
    $type = trim(get_parameter_value($params,'type'));
    $required = cms_to_bool(get_parameter_value($params,'required'));

    $profile = $filepicker->get_profile_or_default($profile_name);
    $parms = [];
    if( $top ) {
        // TODO $top might be Windoze-style absolute path and separator might be \ or /
        //general test for non-absolute path is: !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~',$top)
        if( !startswith($top,'/') ) $top = cmsms()->GetConfig()['uploads_path'].'/'.$top;
        if( startswith($top, CMS_ROOT_PATH ) ) $parms['top'] = $top;
    }
    if( $type ) $parms['type'] = $type;
    if( $parms ) {
        $profile = $profile->overrideWith( $parms );
    }

    $out = $filepicker->get_html( $prefix.$name, $value, $profile, $required );
    if( isset($params['assign']) ) {
        $template->assign( $params['assign'], $out );
        return '';
    } else {
        return $out;
    }
}
