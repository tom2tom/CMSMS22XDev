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
#along with this program. If not, read the licence online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

function smarty_function_cms_filepicker($params,$template)
{
    $filepicker = cms_utils::get_filepicker_module();
    if( !$filepicker ) {
        if( !empty($params['assign']) ) {
            $template->assign(trim($params['assign']),'');
        }
        return '';
    }

    $name = get_parameter_value($params,'name','picker'); //default name, since 2.2.19
    $prefix = get_parameter_value($params,'prefix'); // not a profile property
    $value = get_parameter_value($params,'value'); // ditto
    $required = get_parameter_value($params,'required',false); // ditto

    $top = get_parameter_value($params,'top');
    if( $top ) {
        if( is_absolute_path($top) ) {
            $config = cms_utils::get_config();
            $uploads_path = $config['uploads_path'];
            if( startswith($top,$uploads_path) ) {
                $params['top'] = substr($top,strlen($uploads_path) + 1); //omit leading separator
            }
            else {
                unset($params['top']);
            }
        }
        else {
            $params['top'] = ltrim($top,' \/'); //omit any leading separator
        }
    }
    $profile_name = get_parameter_value($params,'profile');
    $profile = $filepicker->get_profile_or_default($profile_name, '', get_userid(false));
    unset($params['can_upload'],$params['can_delete'],$params['can_mkdir']); // prevent overriding these
    $profile->overrideWith($params);

    $out = $filepicker->get_html($prefix.$name,$value,$profile,$required);
    if( isset($params['assign']) ) {
        $template->assign(trim($params['assign']),$out);
        return '';
    } else {
        return $out;
    }
}
