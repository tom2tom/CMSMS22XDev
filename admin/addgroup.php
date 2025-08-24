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
$active = (isset($_POST['addgroup']) && empty($_POST['active'])) ? 0 : 1;

if (isset($_POST['addgroup'])) {
    try {
        if ($group == '') throw new CmsInvalidDataException(lang('nofieldgiven', lang('groupname')));

        require_once '../lib/classes/class.group.inc.php';

        $groupobj = new Group();
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;

        HookManager::do_hook('Core::AddGroupPre', ['group'=>$groupobj]);

        $result = $groupobj->save();
        if( !$result ) throw new RuntimeException(lang('errorinsertinggroup'));

        HookManager::do_hook('Core::AddGroupPost', ['group'=>$groupobj]);
        // put mention into the admin log
        audit($groupobj->id, 'Admin users group', "Added: $groupobj->name");
        redirect('listgroups.php'.$urlext);
//      return;
    }
    catch( Exception $e ) {
        $error .= '<li>'.$e->GetMessage().'</li>';
    }
}

require_once 'header.php';

$smarty = Smarty_CMS::get_instance(); //also set in header.php
$smarty->assign('header',$themeObject->ShowHeader('addgroup'));
$smarty->assign('hiddenname',CMS_SECURE_PARAM_NAME);
$smarty->assign('hiddenval',$_SESSION[CMS_USER_KEY]);
$smarty->assign('access',$access);
$smarty->assign('active',(bool)$active);
$smarty->assign('error',$error);
$smarty->assign('group',$group);
$smarty->assign('description',$description);
$smarty->display('addgroup.tpl');

require_once 'footer.php';

?>
