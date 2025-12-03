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

check_login();
$urlext = '?' . CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
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
$gCms              = cmsms();
$db                = $gCms->GetDb();
$assign_group_perm = check_permission($userid, 'Manage Groups');
$groupops          = $gCms->GetGroupOperations();
$error             = '';
$adminaccess       = 1;
$active            = 1;
$sel_groups        = [];
// POST[] data
/*
$user              = isset($_POST["user"]) ? cleanValue($_POST["user"]) : '';
$password          = isset($_POST["password"]) ? $_POST["password"] : '';
$passwordagain     = isset($_POST["passwordagain"]) ? $_POST["passwordagain"] : '';
$firstname         = isset($_POST["firstname"]) ? cleanValue($_POST["firstname"]) : '';
$lastname          = isset($_POST["lastname"]) ? cleanValue($_POST["lastname"]) : '';
$email             = isset($_POST["email"]) ? trim(strip_tags($_POST["email"])) : '';
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
            //scrub malicious/XSS & invalid content
            $user = preg_replace('/[^a-zA-Z0-9._\- \x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]/u', '', trim($val));
            break;
        case 'firstname':
        case 'lastname':
            //scrub malicious/XSS & invalid
            $$key = preg_replace(['/[\x00-\x1f\x7f]/', '/<[^>]*>/', '/(<|%3c)(\?|%3f)php.*$/i', '/(<|%3c)(\?|%3f)=?.*$/i'], ['', '', '', ''], trim($val)); //c.f. $sanitize_fn in include.php
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

$copyusersettings = (isset($_POST['copyusersettings'])) ? (int)$_POST['copyusersettings'] : 0;
$adminaccess      = (isset($_POST['adminaccess'])) ? 1 : 0;
$active           = (isset($_POST['active'])) ? 1 : 0;
$sel_groups       = (isset($_POST['sel_groups']) && is_array($_POST['sel_groups'])) ? $_POST['sel_groups'] : $sel_groups;

/*--------------------
 * Variables
 ---------------------*/

if (isset($_POST["submit"])) {

    $validinfo   = true;

    // check for errors
    if ($user == "") { //falsy ok?
        $validinfo = false;
        $error .= "<li>" . lang('nofieldgiven', lang('username')) . "</li>";
    } elseif ($user != trim($_POST['user'])) {
        $validinfo = false;
        $error .= "<li>" . lang('illegalcharacters', lang('username')) . "</li>";
    }

    if ($password == "") { //falsy ok?
        $validinfo = false;
        $error .= "<li>" . lang('nofieldgiven', lang('password')) . "</li>";
    } elseif ($password != $_POST['password']) {
        $validinfo = false;
        $error .= "<li>" . lang('illegalcharacters', lang('password')) . "</li>";
    } elseif ($password != $passwordagain) {
        // We don't want to see this if no password was given
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

    if ($validinfo) {
        $newuser = new User();

        $newuser->username    = $user;
        $newuser->active      = $active;
        $newuser->firstname   = $firstname;
        $newuser->lastname    = $lastname;
        $newuser->email       = $email;
        $newuser->adminaccess = $adminaccess;
        $newuser->SetPassword($password);

        HookManager::do_hook('Core::AddUserPre', [ 'user'=>$newuser ]);

        $result = $newuser->save();

        if ($result) {
            HookManager::do_hook('Core::AddUserPost', [ 'user'=>$newuser ]);

            // set some default preferences, based on the user creating this user
            $adminid = get_userid();
            $userid = $newuser->id;
            if ($copyusersettings > 0) {
                $prefs = cms_userprefs::get_all_for_user($copyusersettings);
                if ($prefs && is_array($prefs)) {
                    foreach ($prefs as $k => $v) {
                        cms_userprefs::set_for_user($userid, $k, $v);
                    }
                }
            } else {
                cms_userprefs::set_for_user($userid, 'default_cms_language', cms_userprefs::get_for_user($adminid, 'default_cms_language'));
                cms_userprefs::set_for_user($userid, 'wysiwyg', cms_userprefs::get_for_user($adminid, 'wysiwyg'));
                cms_userprefs::set_for_user($userid, 'admintheme', get_site_preference('logintheme', CmsAdminThemeBase::GetDefaultTheme()));
                cms_userprefs::set_for_user($userid, 'bookmarks', cms_userprefs::get_for_user($adminid, 'bookmarks'));
                cms_userprefs::set_for_user($userid, 'recent', cms_userprefs::get_for_user($adminid, 'recent'));
            }

            if ($assign_group_perm && is_array($sel_groups) && count($sel_groups)) {
                $iquery = "INSERT INTO " . CMS_DB_PREFIX . "user_groups (user_id,group_id) VALUES (?,?)";
                foreach ($sel_groups as $gid) {
                    $gid = (int)$gid;
                    if ($gid < 1)
                        continue;
                    $db->Execute($iquery, array(
                        $userid,
                        $gid
                    ));
                }
            }

            // put mention into the admin log
            audit($newuser->id, 'Admin user', "Added: $newuser->username");
            redirect('listusers.php' . $urlext);
        } else {
            $error .= "<li>" . lang('errorinsertinguser') . "</li>";
        }
    }
}

/*--------------------
 * Display view
 ---------------------*/

include_once ('header.php');

if ($error != '') {
    echo $themeObject->ShowErrors('<ul class="error">' . $error . '</ul>');
}

$selector = UserOperations::get_instance()->GenerateDropdown(0, 'copyusersettings', [], [-1=>lang('none')]);
if ($assign_group_perm) {
    $groups = GroupOperations::get_instance()->LoadGroups();
    $smarty->assign('groups', $groups);
}

$smarty->assign('adminaccess', $adminaccess);
$smarty->assign('active', $active);
$smarty->assign('user', $user);
$smarty->assign('password', $password);
$smarty->assign('passwordagain', $passwordagain);
$smarty->assign('firstname', $firstname);
$smarty->assign('lastname', $lastname);
$smarty->assign('email', $email);
$smarty->assign('copyusersettings', $copyusersettings);
$smarty->assign('sel_groups', $sel_groups);
$smarty->assign('my_userid', get_userid());
$smarty->assign('userselect', $selector);

$smarty->display('adduser.tpl');

include_once ('footer.php');
?>
