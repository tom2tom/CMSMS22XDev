<?php
#Plugin handler: file_url
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

function smarty_function_file_url($params, $template)
{
    $file = trim(get_parameter_value($params,'file'),' \/');
    if( !$file ) {
        trigger_error('file_url plugin: missing or invalid file parameter');
        return '';
    }

    $config = \cms_config::get_instance();
    $dir = $config['uploads_path'];

    $add_dir = trim(get_parameter_value($params,'dir'),' \/');
    if( $add_dir ) {
        $dir .= DIRECTORY_SEPARATOR.strtr($add_dir,'\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        if( !is_dir($dir) || !is_readable($dir) ) {
            trigger_error("file_url plugin: dir=$add_dir invalid directory name specified");
            return '';
        }
    }

    $fullpath = $dir.DIRECTORY_SEPARATOR.$file;
    if( !is_file($fullpath) || !is_readable($fullpath) ) {
        // try to use $file (if it represents something relative to uploads) as fallback
        $alt = false;
        if( !preg_match('~^(?:/|\\|[A-Z]{1,2}:(\\\\|//))~i',$file) ||
           ((($c = $file[0]) == '/' || $c == '\\') && !startswith($file,CMS_ROOT_PATH)) ) {
            // $file is a relative path
            $file = $config['uploads_path'].DIRECTORY_SEPARATOR.strtr($file,'\/',DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
        }
        if( startswith($file,$config['uploads_path']) ) {
            if( is_file($file) && is_readable($file) ) {
                $helper = new CMSMS\FileTypeHelper($config);
                if( !$helper->is_executable($file) ) {
                    $fullpath = $file;
                    $alt = true;
                }
            }
        }
        if( !$alt ) {
            // no error log here
            return '';
        }
    }

    // convert to url
    $out = str_replace([CMS_ROOT_PATH,'\\'],[CMS_ROOT_URL,'/'],$fullpath);

    $assign = get_parameter_value($params,'assign');
    if( $assign ) {
        $template->assign($assign,$out);
        return '';
    }
    return $out;
}
