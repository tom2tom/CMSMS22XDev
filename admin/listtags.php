<?php
#CMS Made Simple admin console script
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

$CMS_ADMIN_PAGE = 1;
$CMS_LOAD_ALL_PLUGINS = 1;

require_once "../lib/include.php";

check_login();
$userid = get_userid();
$access = check_permission($userid, "View Tag Help");
if( !$access ) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

$plugin = (isset($_GET["plugin"])) ? basename(cleanValue($_GET["plugin"])) : '';
$type = (isset($_GET["type"])) ? basename(cleanValue($_GET["type"])) : '';
$action = (isset($_GET["action"])) ? cleanValue($_GET["action"]) : '';

$config = cmsms()->GetConfig();
$dirs = [];
$dirs[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'plugins';
$dirs[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'plugins';
$dirs[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'plugins';
$dirs[] = $config['admin_path'].DIRECTORY_SEPARATOR.'plugins';

$find_file = function($filename) use($dirs) {
    $dn = DIRECTORY_SEPARATOR.basename($filename); // no sneaky paths
    foreach( $dirs as $one ) {
        $fn = "$one{$dn}";
        if( is_file($fn) ) return $fn;
    }
    return '';
};

require_once "header.php";

$smarty = cmsms()->GetSmarty(); //also in header.php
$smarty->assign('header',$themeObject->ShowHeader('tags'));

if( $action == "showpluginhelp" ) {
    $content = '';
    $file = $find_file("$type.$plugin.php");
    if( is_file($file) ) require_once($file);

    if( function_exists('smarty_cms_help_'.$type.'_'.$plugin) ) {
        // Get and display the plugin's help
        @ob_start();
        call_user_func_array('smarty_cms_help_'.$type.'_'.$plugin, array());
        $content = @ob_get_contents();
        @ob_end_clean();
    }
    elseif( CmsLangOperations::key_exists("help_{$type}_{$plugin}",'tags') ) {
        $content = CmsLangOperations::lang_from_realm('tags',"help_{$type}_{$plugin}");
    }
    elseif( CmsLangOperations::key_exists("help_{$type}_{$plugin}") ) {
        $content = lang("help_{$type}_{$plugin}");
    }

    if( $content ) {
        $smarty->assign('subheader',lang('pluginhelp',array($plugin)));
        $smarty->assign('content',$content);
    }
    else {
        $smarty->assign('error',lang('nopluginhelp'));
    }
}
elseif( $action == "showpluginabout" ) {
    $file = $find_file("$type.$plugin.php");
    if( file_exists($file) ) require_once($file);

    $smarty->assign('subheader',lang('pluginabout',$plugin));
    $func_name = 'smarty_cms_about_'.$type.'_'.$plugin;
    if( function_exists($func_name) ) {
        @ob_start();
        call_user_func_array($func_name, array());
        $content = @ob_get_contents();
        @ob_end_clean();
        $smarty->assign('content',$content);
    }
    else {
        $smarty->assign('error',lang('nopluginabout'));
    }
}
else {
    $urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
    $file_array = array();

    $files = array();
    foreach( $dirs as $one ) {
        $files = array_merge($files,glob($one.'/*.php'));
    }

    if( $files ) {
        foreach( $files as $onefile ) {
            $file = basename($onefile);
            $parts = explode('.',$file);
            if( startswith($file,'prefilter.') || startswith($file,'postfilter.') ) continue;
            if( !is_array($parts) || count($parts) != 3 ) continue;

            $rec = array();
            $rec['type'] = $parts[0];
            $rec['name'] = $parts[1];
            $rec['admin'] = 0;
            if( startswith($onefile,$config['admin_path']) ) $rec['admin'] = 1;

            include_once($onefile);

            if( !function_exists('smarty_'.$rec['type'].'_'.$rec['name']) &&
                !function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name']) &&
                !function_exists('smarty_cms_'.$rec['type'].'_'.$rec['name']) ) continue;

            $rec['cachable'] = 'n_a';
            if( $rec['type'] == 'function' && $rec['admin'] == 0 ) {
                if( function_exists('smarty_cms_'.$rec['type'].'_'.$rec['name']) ) { //this test probably bogus now
                    $rec['cachable'] = 'no';
                }
                elseif( function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name']) ) {
                    $rec['cachable'] = 'no';
                }
                elseif( function_exists('smarty_'.$rec['type'].'_'.$rec['name']) ) {
                    $rec['cachable'] = 'yes';
                }
            }

            if( function_exists("smarty_cms_help_".$rec['type']."_".$rec['name']) ) {
                $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }
            elseif( CmsLangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name'],'tags') ) {
                $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }
            elseif( CmsLangOperations::key_exists('help_'.$rec['type'].'_'.$rec['name']) ) {
                $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }

            if( function_exists("smarty_cms_about_".$rec['type']."_".$rec['name']) ) {
                $rec['about_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginabout&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
            }

            $file_array[] = $rec;
        }
    }

    // add in standard tags...
    $rec = array('type'=>'function','name'=>'content','cachable'=>'no');
    $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = array('type'=>'function','name'=>'content_image','cachable'=>'no');
    $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = array('type'=>'function','name'=>'content_module','cachable'=>'no');
    $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    $rec = array('type'=>'function','name'=>'process_pagedata','cachable'=>'no');
    $rec['help_url'] = 'listtags.php'.$urlext.'&amp;action=showpluginhelp&amp;plugin='.$rec['name'].'&amp;type='.$rec['type'];
    $file_array[] = $rec;

    usort($file_array,function($a,$b) {
        return strcmp($a['name'],$b['name']);
    });

    $smarty->assign('plugins',$file_array);
}

$smarty->display('listtags.tpl');

require_once "footer.php";
