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
require_once ('../lib/include.php');

$urlext = '?' . CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];

check_login();
if (isset($_POST['cancel'])) {
    redirect('listusers.php' . $urlext);
}

$userid = get_userid();
if (!check_permission($userid, 'Manage Users')) {
    exit(lang('no_permission')); //TODO throw if can be caught
}

/*--------------------
 * Variables
 ---------------------*/
$gCms             = cmsms();
$db               = $gCms->GetDb();
$error            = '';
$dropdown         = '';
$adminaccess      = 1;
$active           = 1;
$tplmaster        = 0;
$copyfromtemplate = 1;
$message          = '';
$user_id          = $userid;
if (isset($_GET['user_id'])) {
    $user_id = preg_replace('/[^a-zA-Z0-9._\- \x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]/u', '', trim($_GET['user_id']));
}

// POST[] data
/*
$user             = isset($_POST["user"]) ? cleanValue($_POST["user"]) : '';
$password         = isset($_POST["password"]) ? $_POST["password"] : '';
$passwordagain    = isset($_POST["passwordagain"]) ? $_POST["passwordagain"] : '';
$firstname        = isset($_POST["firstname"]) ? cleanValue($_POST["firstname"]) : '';
$lastname         = isset($_POST["lastname"]) ? cleanValue($_POST["lastname"]) : '';
$email            = isset($_POST["email"]) ? trim(strip_tags($_POST["email"])) : '';
*/
$user          = '';
$password      = '';
$passwordagain = '';
$firstname     = '';
$lastname      = '';
$email         = '';
foreach ($_POST as $key => $val) {
    switch ($key) {
        case 'user': //account
        case 'user_id':
            //scrub malicious/XSS & invalid content
            $$key = preg_replace('/[^a-zA-Z0-9._\- \x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]/u', '', trim($val));
            break;
        case 'firstname':
        case 'lastname':
            //scrub malicious/XSS & invalid
            $$key = preg_replace(['/[\x00-\x1f\x7f]/', '/<[^>]*>/', '/(<|%3c)(\?|%3f)php.*$/i', '/(<|%3c)(\?|%3f)\=?.*$/i'], ['', '', '', ''], trim($val)); //c.f. $sanitize_fn in include.php
            break;
        case 'password':
        case 'passwordagain':
            //scrub malicious/XSS & non-printables
            $$key = preg_replace(['/[\x00-\x1f\x7f]/', '/(<|%3c)(\?|%3f)php.*$/i', '/(<|%3c)(\?|%3f)=?.*$/i'], ['', '', ''], $val);
            break;
        case 'email':
            //TODO scrub XSS & invalid
            //PHP's FILTER_VALIDATE_EMAIL mechanism is incomplete (per RFC5321) - see notes at https://www.php.net/manual/en/function.filter-var.php
            $email = filter_var(trim($val), FILTER_SANITIZE_EMAIL);
    }
}

// this is now always true... but we may want to change how things work, so I'll leave it
$userops           = $gCms->GetUserOperations();
$groupops          = $gCms->GetGroupOperations();
$group_list        = $groupops->LoadGroups();
$access_user       = ($userid == $user_id);
$access_group      = $userops->UserInGroup($userid, 1) || (!$userops->UserInGroup($user_id, 1)); //TODO check logic
$access            = $access_user && $access_group;
$assign_group_perm = check_permission($userid, 'Manage Groups');
$manage_users      = check_permission($userid, 'Manage Users');
$thisuser          = $userops->LoadUserByID($user_id);

/*--------------------
 * Logic
 ---------------------*/

if (isset($_POST["submit"])) {

    if( !$access_user && isset($_POST['active']) ) $active = (int) $_POST['active'];

    $adminaccess = !isset($_POST["adminaccess"]) ? 0 : 1;
    $validinfo   = true;

    // check for errors
    if ($user == "") { //falsy ok?
        $validinfo = false;
        $error .= "<li>" . lang('nofieldgiven', lang('username')) . "</li>";
    } elseif ($user != trim($_POST['user'])) {
        $validinfo = false;
        $error .= "<li>" . lang('illegalcharacters', lang('username')) . "</li>";
    }

    if (isset($_POST["password"]) && $_POST['password'] != $password) {
        $error .= "<li>" . lang('illegalcharacters', lang('password')) . "</li>";
    } elseif ($password != $passwordagain) {
        $validinfo = false;
        $error .= "<li>" . lang('nopasswordmatch') . "</li>";
    }

    if ($email) {
        if ($email != trim($_POST['email'])) {
            $validinfo = false;
            $error .= '<li>' . lang('invalidemail') . '</li>';
        } elseif (!is_email($email)) {
            $validinfo = false;
            $error .= '<li>' . lang('invalidemail') . '</li>';
        }
    }

    if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
        if (isset($_POST['clearusersettings'])) {
            // error: both can't be set
            $validinfo = false;
            $error .= '</li>' . lang('error_multiusersettings') . '</li>';
        }
    }

    // save data
    if ($validinfo) {
        $result = false;
        if ($thisuser) {
            $thisuser->username    = $user;
            $thisuser->firstname   = $firstname;
            $thisuser->lastname    = $lastname;
            $thisuser->email       = $email;
            $thisuser->adminaccess = $adminaccess;
            $thisuser->active      = $active;
            if ($password != '') {
                $thisuser->SetPassword($password);
            }
            HookManager::do_hook('Core::EditUserPre', [ 'user'=>$thisuser ]);

            $result = $thisuser->save();
            if ($assign_group_perm && isset($_POST['groups'])) {
                $dquery = "DELETE FROM " . CMS_DB_PREFIX . "user_groups WHERE user_id=?";
                $result = $db->Execute($dquery, array($thisuser->id));
                $iquery = "INSERT INTO " . CMS_DB_PREFIX . "user_groups (user_id,group_id) VALUES (?,?)";
                foreach ($group_list as $thisGroup) {
                    if (isset($_POST['g' . $thisGroup->id]) && $_POST['g' . $thisGroup->id] == 1) {
                        $result = $db->Execute($iquery, array(
                            $thisuser->id,
                            $thisGroup->id
                        ));
                    }
                }
            }
        }

        audit($userid, 'Admin user', "Edited: $thisuser->username");
        $message = lang('edited_user');

        if ($result) {
            if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
                // copy user preferences from the template user to this user.
                $prefs = cms_userprefs::get_all_for_user((int)$_POST['copyusersettings']);
                if (is_array($prefs) && count($prefs)) {
                    cms_userprefs::remove_for_user($user_id);
                    foreach ($prefs as $k => $v) {
                        cms_userprefs::set_for_user($user_id, $k, $v);
                    }
                    audit($user_id, 'Admin user', 'Settings of user id '.(int)$_POST['copyusersettings'].' copied to '.$thisuser->username);
                    $message = lang('msg_usersettingscopied');
                }
            } else if (isset($_POST['clearusersettings'])) {
                // clear all preferences for this user.
                audit($user_id, 'Admin user', "Cleared all settings of $thisuser->username");
                cms_userprefs::remove_for_user($user_id);
                $message = lang('msg_usersettingscleared');
            }

            // put mention into the admin log
            HookManager::do_hook('Core::EditUserPost', [ 'user'=>$thisuser ]);
            $gCms->clear_cached_files();
            $url = 'listusers.php' . $urlext;
            if ($message) {
                $message = rawurlencode($message);
                $url .= '&message=' . $message;
            }
            redirect($url);
        } else {
            $error .= "<li>" . lang('errorupdatinguser') . "</li>";
        }
    }

} elseif ($user_id != -1) {
    $user        = $thisuser->username;
    $firstname   = $thisuser->firstname;
    $lastname    = $thisuser->lastname;
    $email       = $thisuser->email;
    $adminaccess = $thisuser->adminaccess;
    $active      = $thisuser->active;
}

/*--------------------
 * Display view
 ---------------------*/

include_once ('header.php');

if (!empty($error)) echo $themeObject->ShowErrors('<ul class="error">' . $error . '</ul>');

$out      = array(-1 => lang('none'));
$userlist = UserOperations::get_instance()->LoadUsers();

foreach ($userlist as $one) {
    if ($one->id == $user_id) continue;
    $out[$one->id] = $one->username;
}

if ($assign_group_perm && !$access_user) {
    $groups = GroupOperations::get_instance()->LoadGroups();
    $smarty->assign('groups', $groups);
    $smarty->assign('membergroups', UserOperations::get_instance()->GetMemberGroups($user_id));
}

$smarty->assign('user_id', $user_id);
$smarty->assign('user', $user);
$smarty->assign('firstname', $firstname);
$smarty->assign('lastname', $lastname);
$smarty->assign('email', $email);
$smarty->assign('adminaccess', $adminaccess);
$smarty->assign('active', $active);
$smarty->assign('tplmaster', $tplmaster);
$smarty->assign('copyfromtemplate', $copyfromtemplate);
$smarty->assign('access_user', $access_user);
$smarty->assign('manage_users', $manage_users);
$smarty->assign('users', $out);

$smarty->display('edituser.tpl');

include_once ('footer.php');
?>
