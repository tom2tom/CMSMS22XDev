<?php
#CMS Made Simple admin console script
#(c) 2004-2025 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANthe TY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, read the license online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
#$Id$

$nomod = empty($_REQUEST['mact']);
if( !$nomod ) {
    $ary = explode(',', $_REQUEST['mact'], 4);
    if( empty($ary[0]) ) { // no module specified
        $nomod = true;
    }
}
if( $nomod ) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'index.php';
    return;
}

$CMS_ADMIN_PAGE = 1;
$CMS_MODULE_PAGE = 1;

$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);
$starttime = microtime();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();
$userid = get_userid();
if( isset($_SESSION['cms_passthru']) ) {
    // remove me, this is a hack for something
    $_REQUEST = array_merge($_REQUEST, $_SESSION['cms_passthru']);
    unset($_SESSION['cms_passthru']);
}

$modops = ModuleOperations::get_instance();
$ary = explode(',', cms_htmlentities($_REQUEST['mact']), 4); // this time, with sanitization OR sanitize each $ary[] member
$module = ($ary[0]) ?: ''; // also checked above, without sanitization
$modinst = $modops->get_module_instance($module);
if( !$modinst ) {
    trigger_error('Module '.$module.' not found in memory. This could indicate that the module is in need of upgrade or that there are other problems.');
    return;
}

$id = (!empty($ary[1])) ? $ary[1] : 'm1_';
$action = (!empty($ary[2])) ? $ary[2] : 'defaultadmin';
$params = $modops->GetModuleParameters($id);
$smarty = Smarty_CMS::get_instance();

$USE_THEME = (!isset($_REQUEST['showtemplate']) || $_REQUEST['showtemplate'] != 'false')
 && !(isset($_REQUEST['suppressoutput']) || $modinst->SuppressAdminOutput($_REQUEST));

// module output
if( $USE_THEME ) {
    $themeObject = cms_utils::get_theme_object();
    $themeObject->set_action_module($module);

    // get action output (out-of-order)
    @ob_start();
    echo $modinst->DoActionBase($action, $id, $params, '', $smarty);
    $content = @ob_get_clean();

    // deprecated since 2.2 - just use the hook as follows
    $txt = $modinst->GetHeaderHTML();
    if( $txt ) $themeObject->add_headtext($txt);

    // run hook to get content to be inserted into <head/>
    $all = CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
    if( $all && is_array($all) ) {
        foreach( $all as $txt ) {
            $txt = trim($txt);
            if( $txt ) $themeObject->add_headtext($txt);
        }
    }
    // run hook to get content to be inserted before the </body> tag
    $all = CMSMS\HookManager::do_hook_accumulate('admin_add_bottomtext');
    if( $all && is_array($all) ) {
        foreach( $all as $txt ) {
            $txt = trim($txt);
            if( $txt ) { $themeObject->add_footertext($txt); }
        }
    }

    $title = $themeObject->title;
    // $module_help_type as used here affects $title-processing but has no effect on help-display
    $module_help_type = ($title) ? false : true;
    if( !$title ) $title = $themeObject->get_active_title();
    if( !$title ) $title = $modinst->GetFriendlyName();
    $themeObject->ShowHeader($title, [], '', $module_help_type);
    require_once 'header.php';
    // this is hackish, could otherwise be in a simple template
    echo <<<EOS
<div class="pagecontainer">
 $content
</div>

EOS;
    require_once 'footer.php';
} else {
    echo $modinst->DoActionBase($action, $id, $params, '', $smarty);
}

$obj = new CMSMS\JobCheck();
$obj->initiate_background_processing();
