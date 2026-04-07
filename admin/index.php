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

$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);

$CMS_ADMIN_PAGE = 1;
//$CMS_TOP_MENU = 'main';
//$CMS_ADMIN_TITLE = 'adminhome';
//$CMS_ADMIN_TITLE = 'mainmenu';
//$CMS_EXCLUDE_FROM_RECENT = 1;

require_once '../lib/include.php';

// if this page was accessed directly, and the secure param name is not
// in the URL but it is in the session, assume it is correct.
if( !isset($_GET[CMS_SECURE_PARAM_NAME]) && isset($_SESSION[CMS_USER_KEY]) ) {
    $_GET[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
}

check_login();

$section = (isset($_GET['section'])) ? trim($_GET['section']) : '';
require_once 'header.php';
$smarty->changeCaching(false); // user-permissions might change available links
$themeObject->do_toppage($section);

// run hook to get content to be inserted into <head/>
$all = CMSMS\HookManager::do_hook_accumulate('admin_add_headtext');
if( $all && is_array($all) ) {
    foreach( $all as $txt ) {
        $txt = trim($txt);
        if( $txt ) { $themeObject->add_headtext($txt); }
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
require_once 'footer.php';
