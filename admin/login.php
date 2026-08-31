<?php
#CMS Made Simple admin console script
#(c) 2004-2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
#along with this program. If not, read the license online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
#$Id$

use CMSMS\HookManager;
use CMSMS\internal\LoginOperations;

$CMS_ADMIN_PAGE = 1;
$CMS_LOGIN_PAGE = 1;

require_once("../lib/include.php");
$gCms = CmsApp::get_instance();
$db = $gCms->GetDb();

// if we allow modules to do the login operations
// module registers itself as 'admin login module' in the constructor
// getloginModule
// call the module's getLoginForm() action

$login_ops = LoginOperations::get_instance();

$error = "";
$forgotmessage = "";
$changepwhash = "";

/**
 * Send a lost password recovery email to a specified admin user (by name)
 * @internal
 *
 * @param User $user
 * @return bool result from the attempt to send a message.
 */
function send_recovery_email(User $user)
{
    $gCms = CmsApp::get_instance();
    $config = $gCms->GetConfig();

    $obj = new cms_mailer();
    $obj->IsHTML(TRUE);
    $obj->AddAddress($user->email, html_entity_decode($user->firstname . ' ' . $user->lastname));
    $obj->SetSubject(lang('lostpwemailsubject',html_entity_decode(cms_siteprefs::get('sitename','CMSMS Site'))));
    //$code = anything unique and useless-if-broken and fast
    $code = md5(md5(__FILE__ . '--' . $user->username . md5($user->password.time())));
    cms_userprefs::set_for_user($user->id,'pwreset',$code);
    $url = $config['admin_url'] . '/login.php?recoverme=' . $code;
    $body = lang('lostpwemail',html_entity_decode(cms_siteprefs::get('sitename','CMSMS Site')), $user->username, $url, $url);
    $obj->SetBody($body);

    if( $obj->Send() ) {
        audit($user->id,'Core','Sent lost password email to '.$user->username);
        return true;
    }
    audit($user->id,'Core','Failed to send lost password email to '.$user->username);
    return false;
}

/**
 * Find a user matching the given recovery hash
 * @internal
 *
 * @param string hash to match
 * @return mixed The matching User object if found, or null otherwise.
 */
function find_recovery_user($hash)
{
    if( $hash ) {
        $gCms = CmsApp::get_instance();
        $userops = $gCms->GetUserOperations();
        // TODO avoid creating all User-objects merely to try to find one of them
        foreach ($userops->LoadUsers() as $user) {
            $code = cms_userprefs::get_for_user($user->id, 'pwreset');
            if( $code && $hash === $code ) { //timing attack, hence hash_equals($hash, $code), not a factor here
                return $user;
            }
        }
    }
    return null;
}

//Redirect to the normal login screen if we hit cancel on the forgot pw one
//Otherwise, see if we have a forgot pw hit
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
}
else if (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $userops = $gCms->GetUserOperations();
    $forgot_username = cms_html_entity_decode($_REQUEST['forgottenusername']);
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
    HookManager::do_hook('Core::LostPassword', [ 'username'=>$forgot_username]);
    $oneuser = $userops->LoadUserByUsername($forgot_username);
    unset($_REQUEST['loginsubmit'],$_POST['loginsubmit']);

    if ($oneuser != null) {
        if ($oneuser->email == '') {
            $error = lang('nopasswordforrecovery');
        }
        else if (send_recovery_email($oneuser)) {
            $warningLogin = lang('recoveryemailsent');
        }
        else {
            $error = lang('errorsendingemail');
        }
    }
    else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginFailed', [ 'user'=>$forgot_username ]);
        $error = lang('usernotfound');
    }
}
else if (isset($_REQUEST['recoverme']) && $_REQUEST['recoverme']) {
    $user = find_recovery_user($_REQUEST['recoverme']);
    if ($user == null) {
        $error = lang('usernotfound');
    }
    else {
        $changepwhash = $_REQUEST['recoverme'];
    }
}
else if (isset($_REQUEST['forgotpwchangeform']) && $_REQUEST['forgotpwchangeform']) {
    $user = find_recovery_user($_REQUEST['changepwhash']);
    if ($user == null) {
        $error = lang('usernotfound');
    }
    else if ($_REQUEST['password']) {
        if ($_REQUEST['password'] == $_REQUEST['passwordagain']) {
            $user->SetPassword($_REQUEST['password']);
            $user->Save();
            cms_userprefs::remove_for_user($user->id, 'pwreset');
            $ip_passw_recovery = cms_utils::get_real_ip();
            // put mention into the admin log
            audit($user->id,'Core','Completed lost password recovery for '.$user->username.' (IP: '.$ip_passw_recovery.')');
            HookManager::do_hook('Core::LostPasswordReset', [ 'uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery ]);
            $acceptLogin = lang('passwordchangedlogin');
            $changepwhash = '';
        }
        else {
            $error = lang('nopasswordmatch');
            $changepwhash = $_REQUEST['changepwhash'];
        }
    }
    else {
        $error = lang('nofieldgiven', lang('password'));
        $changepwhash = $_REQUEST['changepwhash'];
    }
    if ($error) sleep(2);
}

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer("Logging out.  Cleaning cookies and session variables.");
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    HookManager::do_hook('Core::LogoutPre', [ 'uid'=>$userid, 'username'=>$username ]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    HookManager::do_hook('Core::LogoutPost', [ 'uid'=>$userid, 'username'=>$username ]);
    audit($userid, 'Admin user', 'Logged out');
}

if( isset($_POST['logincancel']) ) {
    debug_buffer("Login cancelled.  Transferring to frontend.");
    redirect(CMS_ROOT_URL.'/index.php', true);
}
else if( isset($_POST['loginsubmit']) ) {
    // login form submitted
    $login_ops->deauthenticate();
    $username = '';
    if( isset($_POST["username"]) ) $username = cleanValue($_POST["username"]);
    $password = '';
    if( isset($_POST["password"]) ) $password = $_POST["password"];
    unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);

    class CmsLoginError extends CmsException {}

    try {
        if( !$username || !$password ) throw new LogicException(lang('usernameincorrect'));

        $userops = $gCms->GetUserOperations();
        $user = $userops->LoadUserByUsername($username, $password, true, true);
        if( !$user ) throw new CmsLoginError(lang('usernameincorrect'));
        $login_ops->preserve_user($user);

        // send a pre-login event (pre-2.2.7 used key 'user' instead of 'username')
        HookManager::do_hook('Core::LoginPre', [ 'username'=>$username, 'password'=>$password ]);
        // if a LoginPre handler doesn't return here, it must locally perform the following, to resume login
        // $user = $login_ops->retrieve_user(); if ($user) { $login_ops->initialize_authentication($user); }

        $login_ops->initialize_authentication($user);
    }
    catch( Exception $e ) {
        $error = $e->GetMessage();
        debug_buffer("Login failed. Error was: " . $error);
        if( $username ) {
            HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ]);
        }
        // put mention into the admin log
        $ip_login_failed = cms_utils::get_real_ip();
        if( !empty($oneuser) ) {
            $id = $oneuser->id;
        }
        else {
            $id = '';
        }
        audit($id, 'Admin user', "Login failed (IP: $ip_login_failed)");
    }
}

//
// display the login form
//

// Language shizzle
cms_admin_sendheaders();
header("Content-Language: " . CmsNlsOperations::get_current_language());
header("Cache-Control: no-store");
header("Expires: 0");

$themeObject = cms_utils::get_theme_object();
$vars = array('error'=>$error);
if( isset($warningLogin) ) $vars['warningLogin'] = $warningLogin;
if( isset($acceptLogin) ) $vars['acceptLogin'] = $acceptLogin;
if( isset($changepwhash) ) $vars['changepwhash'] = $changepwhash;
$themeObject->do_login($vars);
