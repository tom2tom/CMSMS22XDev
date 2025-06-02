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

require_once '../lib/include.php';
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
    redirect('index.php'.$urlext.'&section=usersgroups');
}

$userid = get_userid(false);
$access = check_permission($userid, 'Manage Groups');
if (!$access) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

$submitted = (isset($_REQUEST["submitted"])) ? (int)$_REQUEST["submitted"] : -1;

$gCms = cmsms();
$userops = $gCms->GetUserOperations();
$adminuser = ($userops->UserInGroup($userid,1) || $userid == 1);
$group_name = '';
$message = '';

require_once 'header.php';

$db = $gCms->GetDb();
$smarty = $gCms->GetSmarty(); //also in header.php

$load_perms = function() use ($db) {
    $query = "SELECT p.permission_id, p.permission_source, p.permission_text, up.group_id FROM ".
    CMS_DB_PREFIX."permissions p LEFT JOIN ".CMS_DB_PREFIX.
    "group_perms up ON p.permission_id = up.permission_id ORDER BY p.permission_text";

    $result = $db->Execute($query);

    // use hooks to localize permissions.
    \CMSMS\HookManager::add_hook('localizeperm',function($perm_name){
            $key = 'perm_'.str_replace(' ','_',$perm_name);
            if( \CmsLangOperations::lang_key_exists('admin',$key) ) return \CmsLangOperations::lang_from_realm('admin',$key);
            return $perm_name;
        },\CMSMS\HookManager::PRIORITY_HIGH);

    \CMSMS\HookManager::add_hook('getperminfo',function($perm_name){
            $key = 'permdesc_'.str_replace(' ','_',$perm_name);
            if( \CmsLangOperations::lang_key_exists('admin',$key) ) return \CmsLangOperations::lang_from_realm('admin',$key);
        // return null
        },\CMSMS\HookManager::PRIORITY_HIGH);

    $perm_struct = array();
    if ($result) {
        while ($row = $result->FetchRow()) {
            foreach (['permission_source','permission_text'] as $fld) {
                if ($row[$fld] === null) $row[$fld] = '';
            }
            if (isset($perm_struct[$row['permission_id']])) {
                $str = &$perm_struct[$row['permission_id']];
                $str->group[$row['group_id']]=1;
            }
            else {
                $thisPerm = new \stdClass();
                $thisPerm->group = array();
                if (!empty($row['group_id'])) $thisPerm->group[$row['group_id']] = 1;
                $thisPerm->id = $row['permission_id'];
                $thisPerm->name = $thisPerm->label = $row['permission_text'];
                $thisPerm->source = $row['permission_source'];
                $thisPerm->label = \CMSMS\HookManager::do_hook_first_result('localizeperm',$thisPerm->name);
                $thisPerm->description = \CMSMS\HookManager::do_hook_first_result('getperminfo',$thisPerm->name);
                $perm_struct[$row['permission_id']] = $thisPerm;
            }
        }
        $result->Close();
    }
    return $perm_struct;
};

$group_perms = function($in_struct) {
    usort($in_struct,function($a,$b){
            // sort by name
            return strcasecmp($a->name,$b->name);
        });

    $out = [];
    foreach( $in_struct as $one ) {
        $source = $one->source;
        if( !isset($out[$source]) ) $out[$source] = [];
        $out[$source][] = $one;
    }

    uksort($out,function($a,$b){
            $a = strtolower($a);
            $b = strtolower($b);
            if( $a == 'core' ) return -1;
            if( $b == 'core' ) return 1;
            if( empty($a) ) return 1;
            if( empty($b) ) return 1;
            return strcmp($a,$b);
        });
    return $out;
};

if( isset($_POST['filter']) ) {
    $disp_group = $_POST['groupsel'];
    cms_userprefs::set_for_user($userid,'changegroupassign_group',$disp_group);
}
$disp_group = cms_userprefs::get_for_user($userid,'changegroupassign_group',-1);

// always display the group pull down
$groupops = $gCms->GetGroupOperations();
$tmp = new stdClass();
$tmp->name = lang('all_groups');
$tmp->id=-1;
$allgroups = array($tmp);
$sel_groups = array($tmp);
$group_list = $groupops->LoadGroups();
$sel_group_ids = array();
foreach( $group_list as $onegroup ) {
    if( $onegroup->id == 1 && $adminuser == false ) continue;
    $allgroups[] = $onegroup;
    if( $disp_group == -1 || $disp_group == $onegroup->id ) {
        $sel_groups[] = $onegroup;
        $sel_group_ids[] = $onegroup->id;
    }
}

$smarty->assign('group_list',$sel_groups);
$smarty->assign('allgroups',$allgroups);

if ($submitted == 1) {
    // we have group permissions
    $now = $db->DbTimeStamp(time());
    $iquery = "INSERT INTO ".CMS_DB_PREFIX.
        "group_perms (group_perm_id, group_id, permission_id, create_date, modified_date)
       VALUES (?,?,?,$now,$now)";

    $parts = explode('::',$_POST['sel_groups']);
    if( count($parts) == 2 ) {
        if( md5(__FILE__.$parts[1]) == $parts[0] ) {
            $selected_groups = (array) json_decode(base64_decode($parts[1]),TRUE);
            if( is_array($selected_groups) && count($selected_groups) ) {
                // clean this array
                $tmp = array();
                foreach( $selected_groups as &$one ) {
                    $one = (int)$one;
                    if( $one > 0 ) $tmp[] = $one;
                }
                unset($one);
                $query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id IN ('.implode(',',$tmp).')';
                $db->Execute($query);
            }
            unset($selected_groups);
        }
    }
    unset($parts);

    foreach ($_POST as $key=>$value) {
        if (strpos($key,"pg") == 0 && strpos($key,"pg") !== false) {
            $keyparts = explode('_',$key);
            $keyparts[1] = (int)$keyparts[1];
            if ($keyparts[1] > 0 && $keyparts[2] != '1' && $value == '1') {
                $new_id = $db->GenID(CMS_DB_PREFIX."group_perms_seq");
                $result = $db->Execute($iquery, array($new_id,$keyparts[2],$keyparts[1]));
                if( !$result ) {
                    echo "FATAL: ".$db->ErrorMsg().'<br>'.$db->sql;
                    exit;
                }
            }
        }
    }

    // put mention into the admin log
//  audit($userid, 'Permission Group ID: '.$userid, 'Changed');
    $usernm = get_username(false);
    audit($userid, 'Admin user', "Changed permissions of $usernm");
    $message = lang('permissionschanged');
    $gCms->clear_cached_files();
}

$perm_struct = $load_perms();

$smarty->assign('perms',$group_perms($perm_struct));
$smarty->assign('hiddenname',CMS_SECURE_PARAM_NAME);
$smarty->assign('hiddenval',$_SESSION[CMS_USER_KEY]);
$smarty->assign('header',$themeObject->ShowHeader('groupperms',array($group_name)));
if( !empty($message) ) $smarty->assign('message',$themeObject->ShowMessage($message));
$smarty->assign('disp_group',$disp_group);
$tmp = base64_encode(json_encode($sel_group_ids));
$sig = md5(__FILE__.$tmp);
$smarty->assign('hidden2','<input type="hidden" name="sel_groups" value="'.$sig.'::'.$tmp.'">');
$smarty->assign('submit','<input type="submit" name="changeperm" value="'.lang('submit').'" class="pagebutton">');
$smarty->assign('cancel','<input type="submit" name="cancel" value="'.lang('cancel').'" class="pagebutton">');
$smarty->display('changegroupperm.tpl');

require_once 'footer.php';
