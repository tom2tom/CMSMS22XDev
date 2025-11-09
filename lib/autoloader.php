<?php
#CMS Made Simple class autoloader
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

/**
 * @package CMS
 * @ignore
 */

/*
function _cms_load($filename)
{
  $gCms = CmsApp::get_instance(); // wierd, but this is required.
  require_once($filename);
}
*/

/**
 * A function for auto-loading classes.
 *
 * @since 1.7
 * @internal
 * @ignore
 * @param string A class name
 * @return boolean
 */
function cms_autoloader($classname)
{
    $gCms = CmsApp::get_instance(); // in case included file checks for this
    $rootp = CMS_ROOT_PATH;
    $sp = DIRECTORY_SEPARATOR;

    $p = strpos($classname, '\\'); //$classname will not contain the leading backslash of a fully-qualified identifier
    if( $p !== false ) {
        // namespaced classes - core and module
        $space = substr($classname,0,$p);
        if( $space == 'CMSMS' ) {
            $sroot = "$rootp{$sp}lib{$sp}classes{$sp}";
        }
        else {
            $mpath = "$rootp{$sp}modules{$sp}{$space}{$sp}{$space}.module.php";
            if( !is_readable($mpath) ) {
                return;
            }
            $sroot = "$rootp{$sp}modules{$sp}{$space}{$sp}lib{$sp}";
        }

        $path = str_replace('\\',DIRECTORY_SEPARATOR,substr($classname,$p + 1));
        $base = basename($path);
        $path = dirname($path);
        if( $path != '.' ) {
            $sroot .= "$path{$sp}";
        }
        foreach( ['class.','interface.','trait.',''] as $test ) {
            $fn = "$sroot{$test}$base.php";
            if( is_file($fn) ) {
                require_once($fn);
                if( class_exists($classname,false) ) return;
            }
        }
        return;
    }

    // standard classes
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}class.{$classname}.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    // standard internal classes
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}internal{$sp}class.{$classname}.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    // lowercase classes
    $lowercase = strtolower($classname);
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}class.{$lowercase}.inc.php";
    if( is_file($fn) && $classname != 'Content' ) {
        require_once($fn);
        return;
    }

    // lowercase internal classes
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}internal{$sp}class.{$lowercase}.inc.php";
    if( is_file($fn) && $classname != 'Content' ) {
        require_once($fn);
        return;
    }

    // standard interfaces
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}interface.{$classname}.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    // internal interfaces
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}internal{$sp}interface.{$classname}.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    // standard content types
    $fn = "$rootp{$sp}lib{$sp}classes{$sp}contenttypes{$sp}{$classname}.inc.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    // modules
    $fn = "$rootp{$sp}modules{$sp}{$classname}{$sp}{$classname}.module.php";
    if( is_file($fn) ) {
        require_once($fn);
        return;
    }

    if( endswith($classname,'Task') ) {
        $class = substr($classname,0,-4);
        $fn = "$rootp{$sp}lib{$sp}tasks{$sp}class.{$class}.task.php";
        if( is_file($fn) ) {
            require_once($fn);
            return;
        }
    }

    // module unspaced classes etc (whether or not the module is loaded)
    $list = glob("$rootp{$sp}modules{$sp}?*{$sp}lib{$sp}{class,interface,trait}.$classname.php",GLOB_BRACE);
    if( !$list ) return;
    $ops = ModuleOperations::get_instance();
    foreach( $list as $fn ) {
        $modname = basename(dirname($fn,2));
        if( $ops->IsModuleActive($modname) || !$ops->IsModuleInstalled($modname) ) { // CHECKME ok test?
            require_once($fn);
            if( class_exists($classname,false) ) return;
        }
    }
}

spl_autoload_register('cms_autoloader');
