<?php
#CMS Made Simple class AdminThemeTemplateResource
#(c) 2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#along with this program; if not, read the licence online at
#https
#
#$Id$

namespace CMSMS\internal;

/**
 * A simple class for handling the admintheme-file Smarty resource.
 *
 * @package CMS
 * @internal
 * @ignore
 * @since 2.2.23F2
 */
class AdminThemeTemplateResource extends Fixed_Resource_Custom
{
    protected function fetch($name,&$source,&$mtime)
    {
        $source = null;
        $mtime = null;
        $params = explode(';',$name);
        if( count($params) != 2 ) return;
        $theme_name = trim($params[0]);
        $filename = basename(trim($params[1]));
        $config = \cms_config::get_instance();
        $fp = \cms_join_path(\CMS_ROOT_PATH,$config['admin_dir'],'themes',$theme_name,'templates',$filename);
        if( file_exists($fp) ) {
            $source = @file_get_contents($fp);
            $mtime = @filemtime($fp);
        }
    }
}
