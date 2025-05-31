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

require_once("../lib/include.php");

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if( isset($_POST['close']) ) {
	redirect('eventhandlers.php'.$urlext);
}

$userid = get_userid();
$access = check_permission($userid, "Modify Events");
if( !$access ) {
	die('Permission Denied'); //TODO throw if can be caught
}

$gCms = cmsms();
$db = $gCms->GetDb();

require_once 'header.php';

//TODO into template
function display_error($text)
{
	echo "<div class=\"pageerrorcontainer\"><p class=\"pageerror\">$text</p></div>\n";
}

$action = '';
$module = '';
$event = '';
$handler = '';

if( isset($_POST['add']) ) {
	// we're adding some funky event handler
	if( !empty($_POST['module']) ) $module = trim(cleanValue($_POST['module']));
	if( !empty($_POST['event']) ) $event = trim(cleanValue($_POST['event']));
	if( !empty($_POST['handler']) ) $handler = trim(cleanValue($_POST['handler']));
	if( $module && $event && $handler ) {
		if( startswith($handler, 'm:') ) {
			$handler = substr($handler, 2);
			Events::AddEventHandler($module, $event, false, $handler);
		}
		else {
			Events::AddEventHandler($module, $event, $handler);
		}
	}
}
else {
	// we're processing an up/down or delete
	if( !empty($_GET['action']) ) $action = trim(cleanValue($_GET['action']));
	if( !empty($_GET['module']) ) $module = trim(cleanValue($_GET['module']));
	if( !empty($_GET['event']) ) $event = trim(cleanValue($_GET['event']));
	if( $module == '' || $event == '' || $action == '' ) {
		display_error(lang('missingparams'));
		return;
	}
	if( !empty($_GET['handler']) ) $handler = (int)$_GET['handler'];
	$cur_order = ( !empty($_GET['order']) ) ? (int)$_GET['order'] : -1;

	switch( $action ) {
	case 'up':
		// move an item up (decrease the order)
		// increases the previous order, and decreases the current handler id
		if( !$handler || $cur_order < 1 ) {
			display_error(lang('missingparams'));
			return;
		}
		Events::OrderHandlerUp($handler);
		break;

	case 'down':
		// move an item down (increase the order)
		// move an item up (decrease the order)
		// increases the previous order, and decreases the current handler id
		if( !$handler || $cur_order < 1 ) {
			display_error(lang('missingparams'));
			return;
		}
		Events::OrderHandlerDown($handler);
		break;

	case 'delete':
		if( !$handler ) {
			display_error( lang('missingparams' ) );
			return;
		}
		Events::RemoveEventHandlerById($handler);
		break;

	default:
		// unknown or unset action
		break;
	} // switch
} // else

// get the event description
$usertagops = $gCms->GetUserTagOperations();

$description = '';
$modulename = '';
if ($module == 'Core') {
	$description = Events::GetEventDescription($event);
	$modulename = lang('core');
}
else {
	$objinstance = cms_utils::get_module($module);
	$description = $objinstance->GetEventDescription($event);
	$modulename = $objinstance->GetFriendlyName();
}

// and now get the list of handlers for this event
$handlers = Events::ListEventHandlers($module, $event);

// and the list of all available handlers
$allhandlers = array();
// we get the list of user tags, and add them to the list
$usertags = $usertagops->ListUserTags();
foreach( $usertags as $value ) {
	$allhandlers[$value] = $value;
}

// and the list of modules, and add them
$allmodules = ModuleOperations::get_instance()->GetInstalledModules();
foreach( $allmodules as $key ) {
	if( $key == $modulename ) continue;
	$modobj = ModuleOperations::get_instance()->get_module_instance($key);
	if( $modobj && $modobj->HandlesEvents() ) {
		$allhandlers[$key] = 'm:'.$key;
	}
}

$downImg = $themeObject->DisplayImage('icons/system/arrow-d.gif', lang('down'),'','','systemicon');
$upImg = $themeObject->DisplayImage('icons/system/arrow-u.gif', lang('up'),'','','systemicon');
$deleteImg = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'),'','','systemicon');

$smarty = Smarty_CMS::get_instance();
$smarty->assign('header',$themeObject->ShowHeader('editeventhandler'));
$smarty->assign('hiddenname',CMS_SECURE_PARAM_NAME);
$smarty->assign('hiddenval',$_SESSION[CMS_USER_KEY]);
$smarty->assign('urlext',$urlext);
$smarty->assign('selfurl','editevent.php');
$smarty->assign('description',$description);
$smarty->assign('event',$event);
$smarty->assign('allhandlers',$allhandlers);
$smarty->assign('handlers',$handlers);
$smarty->assign('icondel',$deleteImg);
$smarty->assign('icondown',$downImg);
$smarty->assign('iconup',$upImg);
$smarty->assign('module',$module);
$smarty->assign('modulename',$modulename);
$smarty->display('editevent.tpl');

require_once 'footer.php';
