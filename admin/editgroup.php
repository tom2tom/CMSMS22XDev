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

use CMSMS\HookManager;

$CMS_ADMIN_PAGE = 1;

require_once '../lib/include.php';
check_login();
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext);
}

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');
if (!$access) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

$error = '';
$group = (isset($_POST['group'])) ? cleanValue($_POST['group']) : '';
$description = (isset($_POST['description'])) ? cleanValue($_POST['description']) : '';
$group_id = (isset($_REQUEST['group_id'])) ? (int)$_REQUEST['group_id'] : -1;
$active = (isset($_POST['editgroup']) && empty($_POST['active']) && $group_id != 1) ? 0 : 1;

$gCms = cmsms();
$userops = $gCms->GetUserOperations();
$useringroup = $userops->UserInGroup($userid,$group_id);

require_once '../lib/classes/class.group.inc.php';

if( $group_id > 0 ) {
    $groupobj = Group::load($group_id);
}
else {
    $groupobj = new Group();
}

if( isset($_POST['editgroup']) ) {
    $validinfo = true;
    if( !$group ) {
        $validinfo = false;
        $error .= '<li>'.lang('nofieldgiven', lang('groupname')).'</li>';
    }

    if( $validinfo ) {
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;
        HookManager::do_hook('Core::EditGroupPre', ['group'=>$groupobj]);

        $result = $groupobj->save();
        if( $result ) {
            HookManager::do_hook('Core::EditGroupPost', ['group'=>$groupobj]);

            // put mention into the admin log
            audit($groupobj->id, 'Admin users group',"Edited: $groupobj->name");
            redirect('listgroups.php'.$urlext);
//          return;
        }
        else {
            $error .= '<li>'.lang('errorupdatinggroup').'</li>';
        }
    }
}
elseif( $group_id != -1 ) {
    $group = $groupobj->name;
    $description = $groupobj->description;
    $active = $groupobj->active;
}

require_once 'header.php';

$themeObject->set_value('pagetitle', 'editgroup');
//if( $group ) $CMS_ADMIN_SUBTITLE = $group; does nothing

$tpl = $smarty->createTemplate('admin_tpl:editgroup.tpl',null, null, $smarty, false);
// see also $smarty-assigned var $secureparam
$tpl->assign('securename',CMS_SECURE_PARAM_NAME)
 ->assign('secureval', $_SESSION[CMS_USER_KEY])
 ->assign('access', $access)
 ->assign('active', (bool)$active)
 ->assign('error', $error)
 ->assign('group_id', $group_id)
 ->assign('useringroup', $useringroup)
 ->assign('group', $group)
 ->assign('description', $description);
$tpl->display();

require_once 'footer.php';
