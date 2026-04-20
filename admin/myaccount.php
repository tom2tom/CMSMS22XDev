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

/*
 * Init variables / objects
 */

$orig_memory = (function_exists('memory_get_usage')?memory_get_usage():0);

$CMS_ADMIN_PAGE = 1;
//$CMS_TOP_MENU = 'admin';
//$CMS_ADMIN_TITLE = 'myaccount';

require_once '../lib/include.php'; // might change the password recorded in $_POST[]
check_login();

$urlext = '?' . CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY];
if( isset($_POST['cancel']) ) {
  redirect('index.php' . $urlext . '&section=usersgroups');
}
$userid = get_userid(); // Also checks login - again!
if( !(check_permission($userid,'Manage My Settings') || check_permission($userid,'Manage My Account')) ) {
  exit(lang('no_permission')); //TODO throw if can be caught
}

$thisurl = basename(__FILE__) . $urlext;
$userobj = UserOperations::get_instance()->LoadUserByID($userid); // <- Safe to do, cause if $userid failed, it redirected to login.
$db = cmsms()->GetDb();
$error = '';
$message = '';

/*
 * Get preferences
 */
$wysiwyg = cms_userprefs::get_for_user($userid, 'wysiwyg');
$ce_navdisplay = cms_userprefs::get_for_user($userid,'ce_navdisplay');
$syntaxhighlighter = cms_userprefs::get_for_user($userid, 'syntaxhighlighter');
$default_cms_language = cms_userprefs::get_for_user($userid, 'default_cms_language');
$old_default_cms_lang = $default_cms_language;
$admintheme = cms_userprefs::get_for_user($userid, 'admintheme');
if (!$admintheme) $admintheme = CmsAdminThemeBase::GetDefaultTheme();
$bookmarks = cms_userprefs::get_for_user($userid, 'bookmarks', 0);
$indent = cms_userprefs::get_for_user($userid, 'indent', true);
$paging = cms_userprefs::get_for_user($userid, 'paging', 0);
$date_format_string = cms_userprefs::get_for_user($userid, 'date_format_string', '%x %X');
$default_parent = cms_userprefs::get_for_user($userid, 'default_parent', -2);
$homepage = cms_userprefs::get_for_user($userid, 'homepage');
$hide_help_links = cms_userprefs::get_for_user($userid, 'hide_help_links', 0);

/*
 * Check tab
 */
$tab = '';
if( isset($_POST['active_tab']) ) { $tab = trim(cleanValue($_POST['active_tab'])); }

/*
 * Submit account
 *
 * NOTE: assumes that we successfully acquired the user object.
 */
if (isset($_POST['submit_account']) && check_permission($userid,'Manage My Account')) {
  // collect params
  $username    = '';
  $password    = '';
  $passwordagain = '';
  $firstname   = '';
  $lastname    = '';
  $email       = '';
  foreach ($_POST as $key => $val) {
    switch ($key) {
      case 'user': //account
        //scrub malicious/XSS & invalid content
        $username = preg_replace('/[^a-zA-Z0-9._\- \x8c\x8e\x9c\x9e\x9f\xc0-\xd6\xd8-\xf6\xf8-\xff\pL\p{Nd}\p{Po}]/u', '', trim($val));
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

  // Do validations
  $validinfo = true;
  if ($username == '') {
    $validinfo = false;
    $error = lang('nofieldgiven', lang('username'));
  }
  elseif ( $username != trim($_POST["user"])) {
    $validinfo = false;
    $error = lang('illegalcharacters', lang('username'));
  }
  elseif ($password && ($password != $passwordagain)) {
    $validinfo = false;
    $error = lang('nopasswordmatch');
  }
  elseif ($email && ($email != trim($_POST['email']) || !is_email($email))) {
    $validinfo = false;
    $error = lang('invalidemail').': '.$email;
  }

  // If success do action
  if ($validinfo) {
    $userobj->username = $username;
    $userobj->firstname = $firstname;
    $userobj->lastname = $lastname;
    $userobj->email = $email;
    HookManager::do_hook('Core::EditUserPre', [ 'user'=>$userobj ]);

    if ($password) $userobj->SetPassword($password);
    $result = $userobj->Save();

    if ($result) {
      // put mention into the admin log
      audit($userid, 'Admin user', "Edited: $userobj->username");
      HookManager::do_hook('Core::EditUserPost', [ 'user'=>$userobj ]);
      $message = lang('accountupdated');
    } else {
      // throw? update just failed
    }
  }
}

/*
 * Record submitted prefs
 */
if (isset($_POST['submit_prefs']) && check_permission($userid,'Manage My Settings')) {
  // Get values from request and drive em to variables
  $tmp = $_POST['wysiwyg'];
  $wysiwyg = ($tmp) ? cleanValue($tmp) : '';
  $ce_navdisplay = cleanValue($_POST['ce_navdisplay']);
  $tmp = $_POST['syntaxhighlighter'];
  $syntaxhighlighter =  ($tmp) ? cleanValue($tmp) : '';
  $default_cms_language = '';
  if (isset($_POST['default_cms_language'])) $default_cms_language = cleanValue($_POST['default_cms_language']);
  $old_default_cms_lang = '';
  if (isset($_POST['old_default_cms_lang'])) $old_default_cms_lang = cleanValue($_POST['old_default_cms_lang']);
  if (isset($_POST['admintheme'])) { $admintheme = cleanValue($_POST['admintheme']); }
  else { $admintheme = null; } //aka unset
  $bookmarks = (!empty($_POST['bookmarks']) ? 1 : 0);
  $indent = (!empty($_POST['indent']));
  $paging = (!empty($_POST['paging']) ? 1 : 0);
  $date_format_string = trim(strip_tags(substr($_POST['date_format_string'], 0, 20)));
  $default_parent = '';
  if (isset($_POST['parent_id'])) $default_parent = (int)$_POST['parent_id'];
  $homepage = cleanValue($_POST['homepage']);
  $hide_help_links = (!empty($_POST['hide_help_links']) ? 1 : 0);

  // Set prefs
  cms_userprefs::set_for_user($userid, 'wysiwyg', $wysiwyg);
  cms_userprefs::set_for_user($userid, 'ce_navdisplay', $ce_navdisplay);
  cms_userprefs::set_for_user($userid, 'syntaxhighlighter', $syntaxhighlighter);
  cms_userprefs::set_for_user($userid, 'default_cms_language', $default_cms_language);
  if (isset($admintheme)) cms_userprefs::set_for_user($userid, 'admintheme', $admintheme);
  cms_userprefs::set_for_user($userid, 'bookmarks', $bookmarks);
  cms_userprefs::set_for_user($userid, 'hide_help_links', $hide_help_links);
  cms_userprefs::set_for_user($userid, 'indent', $indent);
  cms_userprefs::set_for_user($userid, 'paging', $paging);
  cms_userprefs::set_for_user($userid, 'date_format_string', strip_tags($date_format_string));
  cms_userprefs::set_for_user($userid, 'default_parent', $default_parent);
  cms_userprefs::set_for_user($userid, 'homepage', $homepage);

  // Audit, message, cleanup
  audit($userid, 'Admin user', "Edited: $userobj->username");
  $message = lang('prefsupdated');
  cmsms()->clear_cached_files();
}

/*
 * Build page
 */
require_once 'header.php';

if ($error) {
  $themeObject->ShowErrors($error);
}
if ($message) {
  $themeObject->ShowMessage($message);
}

$tpl = $smarty->createTemplate('admin_tpl:myaccount.tpl', null, null, $smarty, false);
// see also $smarty-assigned var $secureparam
$tpl->assign('securename', CMS_SECURE_PARAM_NAME) // defined in include.php?
 ->assign('secureval', $_SESSION[CMS_USER_KEY]) // set in include.php?
 ->assign('tab', $tab);

$contentops = cmsms()->GetContentOperations();
// html editor
$tmp = module_meta::get_instance()->module_list_by_capability(CmsCoreCapabilities::WYSIWYG_MODULE);
$tmp2 = array('' => lang('none'));
for ($i = 0; $i < count($tmp); $i++) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$tpl->assign('wysiwyg_opts', $tmp2);

// syntax highlight editor
$tmp = module_meta::get_instance()->module_list_by_capability(CmsCoreCapabilities::SYNTAX_MODULE);
$tmp2 = array('' => lang('none'));
for ($i = 0; $i < count($tmp); $i++) {
  $tmp2[$tmp[$i]] = $tmp[$i];
}

$tpl->assign('syntax_opts', $tmp2);

// admin themes
$allthemes = (array)CmsAdminThemeBase::GetAvailableThemes();

$pagesel = $contentops->CreateHierarchyDropdown(0, $default_parent, 'parent_id', false, true, false, false, false, 'selparent');

$tpl->assign('wysiwyg', $wysiwyg);
$tpl->assign('ce_navdisplay', $ce_navdisplay);
$tpl->assign('syntaxhighlighter', $syntaxhighlighter);
$tpl->assign('language_opts', get_language_list());
$tpl->assign('default_cms_language', $default_cms_language);
$tpl->assign('old_default_cms_lang', $old_default_cms_lang);
$tpl->assign('bookmarks', $bookmarks);
if( count($allthemes) > 1 ) {
  $tpl->assign('themes_opts', $allthemes);
  $tpl->assign('admintheme', $admintheme);
}
$tpl->assign('hide_help_links', $hide_help_links);
$tpl->assign('indent', $indent);
$tpl->assign('paging', $paging);
$tpl->assign('date_format_string', $date_format_string);
$tpl->assign('default_parent', $pagesel);
$tpl->assign('homepage', $themeObject->GetAdminPageDropdown('homepage', $homepage, 'homepage'));
$tpl->assign('pagelimit_opts', [10 => 10, 20 => 20, 50 => 50, 100 => 100]);
$tpl->assign('backurl', $themeObject->backUrl());
$tpl->assign('formurl', $thisurl);
$tpl->assign('userobj', $userobj);
$tpl->assign('manageaccount', check_permission($userid,'Manage My Account'));
$tpl->assign('managesettings', check_permission($userid,'Manage My Settings'));
$tpl->display();

require_once 'footer.php';
