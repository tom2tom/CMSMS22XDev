<?php
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#Visit our homepage at: http://www.cmsmadesimple.org
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
#$Id: deletegroup.php 12671 2021-12-13 03:05:01Z tomphantoo $

$CMS_ADMIN_PAGE=1;

require_once("../lib/include.php");
require_once("../lib/classes/class.group.inc.php");
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

$group_id = -1;
if (isset($_GET["group_id"])) {
    $group_id = $_GET["group_id"];

    if( $group_id == 1 ) {
        // can't delete this group
        redirect("listgroups.php".$urlext);
    }

    $group_name = "";
    $userid = get_userid();
    $access = check_permission($userid, 'Manage Groups');

    if( !$access ) {
        // you can't delete admin group (also admin group it's the first group)
        // no access
        redirect("listgroups.php".$urlext);
    }

    $result = false;

    $gCms = cmsms();
    $groupops = $gCms->GetGroupOperations();
    $userops = $gCms->GetUserOperations();
    $groupobj = $groupops->LoadGroupByID($group_id);
    $group_name = $groupobj->name;

    if( $userops->UserInGroup($userid,$group_id) ) {
        // check to make sure we're not a member of this group
        // can't delete a group we're a member of.
        redirect("listgroups.php".$urlext);
    }

    // now do the work.
    \CMSMS\HookManager::do_hook('Core::DeleteGroupPre', [ 'group'=>&$groupobj ] );

    if ($groupobj) $result = $groupobj->Delete();

    \CMSMS\HookManager::do_hook('Core::DeleteGroupPost', [ 'group'=>&$groupobj ] );

    if ($result == true) {
        // put mention into the admin log
        audit($group_id, 'Admin User Group: '.$group_name, 'Deleted');
    }
}

redirect("listgroups.php".$urlext);
