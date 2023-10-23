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
#but WITHOUT ANY WARRANthe TY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
#$Id: moduleinterface.php 12564 2020-09-27 15:43:03Z ruudvdvelden $

$CMS_ADMIN_PAGE=1;
$CMS_MODULE_PAGE=1;

$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);
$starttime = microtime();

require_once("../lib/include.php");
//$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();
$userid = get_userid();
if( isset($_SESSION['cms_passthru']) ) {
    // remove me, this is a hack for something
    $_REQUEST = array_merge($_REQUEST,$_SESSION['cms_passthru']);
    unset($_SESSION['cms_passthru']);
}

$smarty = \Smarty_CMS::get_instance();
// $smarty->assign('date_format_string',cms_userprefs::get_for_user($userid,'date_format_string','%x %X'));

$id = 'm1_';
$module = '';
$action = 'defaultadmin'; // or 'default' if this is ultimately a frontend request
$suppressOutput = false;
if (isset($_REQUEST['mact'])) {
    $ary = explode(',', cms_htmlentities($_REQUEST['mact']), 4);
    if ($ary[0]) $module = $ary[0];
    if (!empty($ary[1])) $id = $ary[1];
    if (!empty($ary[2])) $action = $ary[2];
}

$modinst = ModuleOperations::get_instance()->get_module_instance($module);
if( !$modinst ) {
    trigger_error('Module '.$module.' not found in memory. This could indicate that the module is in need of upgrade or that there are other problems');
    redirect('index.php');
}

$USE_THEME = true;
if( isset($_REQUEST['showtemplate']) && ($_REQUEST['showtemplate'] == 'false')) {
    // for simplicity and compatibility with the frontend.
    $USE_THEME = false;
}
if( $USE_THEME && $modinst->SuppressAdminOutput($_REQUEST) != false || isset($_REQUEST['suppressoutput']) ) $USE_THEME = false;

// module output
$params = ModuleOperations::get_instance()->GetModuleParameters($id);
if( $USE_THEME ) {
    $themeObject = cms_utils::get_theme_object();
    $themeObject->set_action_module($module);

    // get module output
    @ob_start();
    echo  $modinst->DoActionBase($action, $id, $params, '', $smarty);
    $content = @ob_get_contents();
    @ob_end_clean();

    // deprecate this.
    $txt = $modinst->GetHeaderHTML($action);
    if( $txt ) $themeObject->add_headtext($txt);

    // call admin_add_headtext to get any admin data to add to the <head>
    $out = \CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
    if( $out && !empty($out) ) {
        foreach( $out as $one ) {
            $one = trim($one);
            if( $one ) $themeObject->add_headtext($one);
        }
    }

    include_once("header.php");

    // this is hackish
    echo '<div class="pagecontainer">';
    echo '<div class="pageoverflow">';
    $title = $themeObject->title;
    $module_help_type = 'both';
    if( $title ) $module_help_type = '';
    if( !$title ) $title = $themeObject->get_active_title();
    if( !$title ) $title = $modinst->GetFriendlyName();
    echo $themeObject->ShowHeader($title,'','',$module_help_type).'</div>';
    echo $content;
    echo '</div>';
    include_once("footer.php");
} else {
    echo $modinst->DoActionBase($action, $id, $params, '', $smarty);
}
