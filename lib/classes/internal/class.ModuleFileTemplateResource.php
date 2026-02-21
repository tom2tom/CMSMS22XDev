<?php
#CMS Made Simple classes ModuleDbTemplateResource and ModuleFileTemplateResource
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
#
#$Id$

namespace CMSMS\internal;

/**
 * A simple class to handle a module file template.
 *
 * @ignore
 * @internal
 * @since 1.11
 * @package CMS
 */
class ModuleFileTemplateResource extends Fixed_Resource_Custom
{
    protected function fetch($name,&$source,&$mtime)
    {
        $source = null;
        $mtime = null;
        $params = explode(';',$name);
        if( count($params) != 2 ) return;

        $module_name = trim($params[0]);
        $filename = basename(trim($params[1]));
        $config = \cms_config::get_instance();
        $files = array();
        $files[] = \cms_join_path($config['assets_path'],'module_custom',$module_name,'templates',$filename);
        $files[] = \cms_join_path(\CMS_ROOT_PATH,'modules',$module_name,'templates',$filename);

        foreach( $files as $one ) {
            if( file_exists($one) ) {
                $source = @file_get_contents($one);
                $mtime = @filemtime($one);
                break;
            }
        }
    }
}
class_alias(ModuleFileTemplateResource::class,'CMSModuleFileTemplateResource',false); //deprecated
