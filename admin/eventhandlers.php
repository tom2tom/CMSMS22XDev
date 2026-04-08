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
//$CMS_LOAD_ALL_PLUGINS = 1; ?

require_once '../lib/include.php';

check_login();

$userid = get_userid();
$access = check_permission($userid, "Modify Events");
if (!$access) {
	exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';

$action = (!empty($_REQUEST['action'])) ? $_REQUEST['action'] : '';
$event = (!empty($_REQUEST['event'])) ? $_REQUEST['event'] : '';
$originator = (!empty($_REQUEST['originator'])) ? $_REQUEST['originator'] : '';
$modulefilter = (!empty($_REQUEST['modulefilter'])) ? $_REQUEST['modulefilter'] : '';

$themeObject->set_value('pagetitle', 'eventhandlers');

$smarty->changeCaching(false);
$tplname = ($action == 'showeventhelp') ? 'eventhelp.tpl' : 'listevents.tpl';
$tpl = $smarty->createTemplate("admin_tpl:$tplname",null,null,$smarty,false);
// see also $smarty-assigned var $secureparam
$tpl->assign('securename',CMS_SECURE_PARAM_NAME);
$tpl->assign('secureval',$_SESSION[CMS_USER_KEY]);

switch( $action ) {
	case 'showeventhelp':
		if( $originator == 'Core' ) {
			$desctext = Events::GetEventDescription($event);
			$text = Events::GetEventHelp($event);
		}
		else {
			$moduleobj = cms_utils::get_module($originator);
			if( is_object($moduleobj) ) {
				$desctext = $moduleobj->GetEventDescription($event);
				$text = $moduleobj->GetEventHelp($event);
			}
			else {
				$desctext = '';
				$text = 'No helptext available...';
			}
		}
		if( $text && strpos($text,'Parameters') !== false ) {
			$text = str_replace('Parameters',lang('parameters'),$text);
		}
		$handlers = Events::ListEventHandlers($originator,$event,true); //array, maybe empty

		$tpl->assign('desctext',$desctext)
		->assign('event',$event)
		->assign('hlist',$handlers)
		->assign('text',$text);
		break;

	default:
		$modlist = [];
		$events = Events::ListEvents();
		if( $events ) {
			foreach( $events as $oneevent ) {
				if( !in_array($oneevent['originator'],$modlist) ) {
					$modlist[] = $oneevent['originator'];
				}
			}
		}
		$editicon = $themeObject->DisplayImage('icons/system/edit.gif',lang('edit'),'','','systemicon');
		$infoicon = $themeObject->DisplayImage('icons/system/info.gif',lang('help'),'','','systemicon');

		$tpl->assign('access',$access)
		->assign('editImg',$editicon)
		->assign('events',$events)
		->assign('infoImg',$infoicon)
		->assign('modlist',$modlist)
		->assign('modulefilter',$modulefilter);
}

$tpl->display();

require_once 'footer.php';
