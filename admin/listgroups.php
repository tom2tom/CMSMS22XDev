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

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups'); // 'Add Groups' ok?
if (!$access) {
	exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';

$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 20; // max items per page

$showgroups = [];
$gCms = cmsms();
$groupops = $gCms->GetGroupOperations();
$grouplist = $groupops->LoadGroups();
if ($grouplist) {
	$userops = $gCms->GetUserOperations();
	$gmax = $page * $limit;
	for ($ctr = $gmax - $limit; $ctr < $gmax; $ctr++) {
		if (isset($grouplist[$ctr])) {
			$group = $grouplist[$ctr];
			$showgroups[] = [$group,$userops->UserInGroup($userid,$group->id)];
		}
	}
}

$smarty = Smarty_CMS::get_instance(); // also defined in header.php
$smarty->assign('padd',check_permission($userid,'Add Groups'));
$smarty->assign('header',$themeObject->ShowHeader('currentgroups'));
$smarty->assign('iconadd',$themeObject->DisplayImage('icons/system/newobject.gif',lang('addgroup'),'','','systemicon'));
$smarty->assign('iconedit',$themeObject->DisplayImage('icons/system/edit.gif',lang('editgroup'),'','','systemicon'));
$smarty->assign('icondelete',$themeObject->DisplayImage('icons/system/delete.gif',lang('delete'),'','','systemicon'));
$smarty->assign('icontrue',$themeObject->DisplayImage('icons/system/true.gif',lang('true'),'','','systemicon'));
$smarty->assign('iconfalse',$themeObject->DisplayImage('icons/system/false.gif',lang('false'),'','','systemicon'));
$smarty->assign('icongroup',$themeObject->DisplayImage('icons/system/groupassign.gif',lang('assignments'),'','','systemicon'));
$smarty->assign('iconperms',$themeObject->DisplayImage('icons/system/permissions.gif',lang('permissions'),'','','systemicon'));
$smarty->assign('urlext','?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY]);
if (($n = count($grouplist)) > $limit) {
	$smarty->assign('pagination',pagination($page,$n,$limit));
}
$smarty->assign('grouplist',$showgroups);
$smarty->display('listgroups.tpl');

require_once 'footer.php';

?>
