<?php
global $admin_user;

status_msg(ilang('install_requireddata'));

$query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (203)';
$db->Execute($query);
verbose_msg(ilang('install_setschemaver'));

//
// site preferences
//
verbose_msg(ilang('install_initsiteprefs'));
cms_siteprefs::set('adminlog_lifetime',86400*31); // admin log entries only live for 31 days.
cms_siteprefs::set('allow_browser_cache',1); // allow browser to cache cachable pages
cms_siteprefs::set('auto_clear_cache_age',60); // cache files for only 60 days by default
cms_siteprefs::set('browser_cache_expiry',60); // browser can cache pages for 60 minutes.
$idx = mt_rand(0,15);
$prime = [211,223,227,229,233,239,241,251,257,263,269,271,277,281,283,293][$idx];
$xorer = mt_rand(200,300);
cms_siteprefs::set('fuscint',"$prime.$xorer"); // userid obfuscation params
$txt = str_pad(decoct(umask()),3,'0',STR_PAD_LEFT);
cms_siteprefs::set('global_umask',$txt); // deprecated since 2.2.19 (setting umask is bad on multi-threaded servers)
cms_siteprefs::set('metadata',"<meta name=\"Generator\" content=\"CMS Made Simple - Copyright (C) 2004-" . date('Y') . ". All rights reserved.\">\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\r\n");
cms_siteprefs::set('privatePath',"\$config['admin_path'],configs,private");
cms_siteprefs::set('sitedownmessage','<p>Site is currently down for maintenance</p>');
cms_siteprefs::set('use_smartycache',1); // cache templates
cms_siteprefs::set('use_smartycompilecheck',0); // do not compile-check templates
cms_siteprefs::set('SmartyAdmincacheLife',30); // Smarty admin-templates cached for 30 minutes, if at all
cms_siteprefs::set('SmartyFrontcacheLife',60); // Smarty frontend-templates cached for 60 minutes, if at all
/* other prefs used across the core
'backendwysiwyg'
'basic_attributes'
'browser_cache_expiry'
'cache_driver'
'cache_filecache_autocleaning'
'cache_filecache_blocking'
'cache_filecache_lifetime'
'cache_filecache_locking'
'checkversion'
'content_autocreate_flaturls'
'content_autocreate_urls'
'content_cssnameisblockname'
'content_imagefield_path'
'content_mandatory_urls'
'content_thumbnailfield_path'
'contentimage_path'
'custom404'
'defaultdateformat'
'disallowed_contenttypes'
'enablenotifications'
'enablesitedownmessage'
'filepickermodule'
'frontendlang'
'frontendwysiwyg'
'job_maxerrs',5
'jobs_interval',15
'jobs_timeout',30
'last_remotever_check'
'last_remotever'
'lock_timeout'
'logintheme'
'mail_is_set'
'mailprefs'
'notices_timeout'
'searchmodule'
'sitedown_use_wysiwyg'
'sitedownexcludeadmins'
'sitedownexcludes'
'sitemask'
'sitename'
'template_userid' disabled
'thumbnail_height'
'thumbnail_width'
'xmlmodulerepository'
*/
//
// permissions
//
verbose_msg(ilang('install_initsiteperms'));
$all_perms = array();
$perms = array(
	'Add Pages','Manage Groups','Add Templates','Manage Users','Modify Any Page',
	'Modify Permissions','Modify Templates','Remove Pages',
	'Modify Modules','Modify Files','Modify Site Preferences','Manage Jobs',
	'Manage Stylesheets','Manage Designs','Modify User-defined Tags','Clear Admin Log',
	'Modify Events','View Tag Help','Manage All Content','Reorder Content','Manage My Settings',
	'Manage My Account','Manage My Bookmarks'
	);
foreach( $perms as $one_perm ) {
  $permission = new CmsPermission();
  $permission->source = 'Core';
  $permission->name = $one_perm;
  $permission->text = $one_perm;
  $permission->save();
  $all_perms[$one_perm] = $permission;
}

//
// initial groups
//
verbose_msg(ilang('install_initsitegroups'));
$admin_group = new Group();
$admin_group->name = 'Admin';
$admin_group->description = 'Members of this group can manage the entire site.';
$admin_group->active = 1;
$admin_group->Save();

$editor_group = new Group();
$editor_group->name = 'Editor';
$editor_group->description = 'Members of this group can manage content';
$editor_group->active = 1;
$editor_group->Save();
$editor_group->GrantPermission('Manage All Content');
$editor_group->GrantPermission('Manage My Account');
$editor_group->GrantPermission('Manage My Settings');
$editor_group->GrantPermission('Manage My Bookmarks');

$designer_group = new Group();
$designer_group->name = 'Designer';
$designer_group->description = 'Members of this group can manage stylesheets, templates, and content';
$designer_group->active = 1;
$designer_group->Save();
$designer_group->GrantPermission('Add Templates');
$designer_group->GrantPermission('Manage Designs');
$designer_group->GrantPermission('Modify Templates');
$designer_group->GrantPermission('Manage Stylesheets');
$designer_group->GrantPermission('Manage All Content');
$designer_group->GrantPermission('Manage My Account');
$designer_group->GrantPermission('Manage My Settings');
$designer_group->GrantPermission('Manage My Bookmarks');
$designer_group->GrantPermission('Modify Files');
$designer_group->GrantPermission('Modify User-defined Tags');

//
// initial user account
//
verbose_msg(ilang('install_initsiteusers'));
$admin_user = new User();
$admin_user->username = $adminaccount['username'];
if( !empty($adminaccount['emailaddr']) ) $admin_user->email = $adminaccount['emailaddr'];
$admin_user->active = 1;
$admin_user->adminaccess = 1;
$admin_user->password = password_hash($adminaccount['password'],PASSWORD_BCRYPT); //PASSWORD_ARGON2I or PASSWORD_ARGON2ID might be relevant in future
$admin_user->Save();
UserOperations::get_instance()->AddMemberGroup($admin_user->id,$admin_group->id);
cms_userprefs::set_for_user($admin_user->id,'wysiwyg','MicroTiny'); // the only user-preference needed now.

//
// User Tags
//
verbose_msg(ilang('install_initsiteusertags'));
UserTagOperations::get_instance()->SetUserTag('user_agent',
  "//Code to show the users user agent information.
echo \$_SERVER['HTTP_USER_AGENT'];",
  'Code to show the user\'s user agent information');

$txt = <<<EOT
//set start to date your site was published\n\$startCopyRight='2004';\n\n// check if start year is this year\nif(date('Y') == \$startCopyRight){\n// it was, just print this year\n    echo \$startCopyRight;\n}else{\n// it wasnt, print startyear and this year delimited with a dash\n    echo \$startCopyRight.'-'. date('Y');\n}
EOT;
UserTagOperations::get_instance()->SetUserTag('custom_copyright',$txt,'Code to output copyright information');

//
// Events
//
verbose_msg(ilang('install_initevents'));
Events::CreateEvent('Core','LoginPre');
Events::CreateEvent('Core','LoginPassed');
Events::CreateEvent('Core','LoginPost');
Events::CreateEvent('Core','LogoutPre');
Events::CreateEvent('Core','LogoutPost');
Events::CreateEvent('Core','LoginFailed');
Events::CreateEvent('Core','LostPassword');
Events::CreateEvent('Core','LostPasswordReset');

Events::CreateEvent('Core','AddUserPre');
Events::CreateEvent('Core','AddUserPost');
Events::CreateEvent('Core','EditUserPre');
Events::CreateEvent('Core','EditUserPost');
Events::CreateEvent('Core','DeleteUserPre');
Events::CreateEvent('Core','DeleteUserPost');
Events::CreateEvent('Core','AddGroupPre');
Events::CreateEvent('Core','AddGroupPost');
Events::CreateEvent('Core','EditGroupPre');
Events::CreateEvent('Core','EditGroupPost');
Events::CreateEvent('Core','DeleteGroupPre');
Events::CreateEvent('Core','DeleteGroupPost');

Events::CreateEvent('Core','AddStylesheetPre');
Events::CreateEvent('Core','AddStylesheetPost');
Events::CreateEvent('Core','EditStylesheetPre');
Events::CreateEvent('Core','EditStylesheetPost');
Events::CreateEvent('Core','DeleteStylesheetPre');
Events::CreateEvent('Core','DeleteStylesheetPost');
Events::CreateEvent('Core','AddTemplatePre');
Events::CreateEvent('Core','AddTemplatePost');
Events::CreateEvent('Core','EditTemplatePre');

Events::CreateEvent('Core','EditTemplatePost');
Events::CreateEvent('Core','DeleteTemplatePre');
Events::CreateEvent('Core','DeleteTemplatePost');
Events::CreateEvent('Core','AddTemplateTypePre');
Events::CreateEvent('Core','AddTemplateTypePost');
Events::CreateEvent('Core','EditTemplateTypePre');
Events::CreateEvent('Core','EditTemplateTypePost');
Events::CreateEvent('Core','DeleteTemplateTypePre');
Events::CreateEvent('Core','DeleteTemplateTypePost');
Events::CreateEvent('Core','AddDesignPre');
Events::CreateEvent('Core','AddDesignPost');
Events::CreateEvent('Core','EditDesignPre');
Events::CreateEvent('Core','EditDesignPost');
Events::CreateEvent('Core','DeleteDesignPre');
Events::CreateEvent('Core','DeleteDesignPost');

Events::CreateEvent('Core','TemplatePreCompile');
Events::CreateEvent('Core','TemplatePreFetch');
Events::CreateEvent('Core','TemplatePostCompile');

Events::CreateEvent('Core','ContentEditPre');
Events::CreateEvent('Core','ContentEditPost');
Events::CreateEvent('Core','ContentDeletePre');
Events::CreateEvent('Core','ContentDeletePost');

Events::CreateEvent('Core','AddUserDefinedTagPre');
Events::CreateEvent('Core','AddUserDefinedTagPost');
Events::CreateEvent('Core','EditUserDefinedTagPre');
Events::CreateEvent('Core','EditUserDefinedTagPost');
Events::CreateEvent('Core','DeleteUserDefinedTagPre');
Events::CreateEvent('Core','DeleteUserDefinedTagPost');

Events::CreateEvent('Core','ModuleInstalled');
Events::CreateEvent('Core','ModuleUninstalled');
Events::CreateEvent('Core','ModuleUpgraded');
Events::CreateEvent('Core','OnJobFailed');

Events::CreateEvent('Core','ContentPreCompile');
Events::CreateEvent('Core','ContentPostCompile');
Events::CreateEvent('Core','ContentPreRender'); // 2.2
Events::CreateEvent('Core','ContentPostRender');
Events::CreateEvent('Core','SmartyPreCompile');
Events::CreateEvent('Core','SmartyPostCompile');
Events::CreateEvent('Core','ChangeGroupAssignPre');
Events::CreateEvent('Core','ChangeGroupAssignPost');
Events::CreateEvent('Core','StylesheetPreCompile');
Events::CreateEvent('Core','StylesheetPostCompile');
Events::CreateEvent('Core','StylesheetPostRender');

$perms = 0777; //TODO suitable new-folder permissions e.g. 0777 & ~umask()
$create_private_dir = function($top_dir,...$relative_dir) use($perms) {
    if( !$relative_dir ) return;
    $sep = DIRECTORY_SEPARATOR;
    $dn = implode($sep,$relative_dir);
    $dir = "$top_dir{$sep}$dn";
    if( !is_dir($dir) ) {
        @mkdir($dir,$perms,true);
    }
    @touch("$dir{$sep}index.html");
};
/*
$move_directory_files = function($srcdir,$destdir) {
    $srcdir = trim($srcdir);
    $destdir = trim($destdir);
    if( !is_dir($srcdir) ) return;

    $files = glob($srcdir.'/*');
    if( !$files ) return;

    foreach( $files as $src ) {
        $bn = basename($src);
        $dest = $destdir.'/'.$bn;
        rename($src,$dest);
    }
    @touch($dir.'/index.html');
};
*/
$app = \__appbase\get_app();
$destdir = $app->get_destdir();

// create directories excluded from sources archive cuz empty
verbose_msg(ilang('install_createtmpdirs'));
$create_private_dir($destdir,'tmp');
$create_private_dir($destdir,'tmp','cache');
$create_private_dir($destdir,'tmp','config');
$create_private_dir($destdir,'tmp','templates_c');

$create_private_dir($destdir,'uploads'); // since 2.2.22F2 not in archive if themes data not in here
$create_private_dir($destdir,'uploads','images');
$create_private_dir($destdir,'admin','configs'); // since 2.2.20
$create_private_dir($destdir,'admin','configs','private'); // since 2.2.23F2
// create the assets directory structure
verbose_msg(ilang('install_createassets'));
$create_private_dir($destdir,'assets');
$create_private_dir($destdir,'assets','admin_custom');
$create_private_dir($destdir,'assets','configs');
$create_private_dir($destdir,'assets','css');
$create_private_dir($destdir,'assets','images');
$create_private_dir($destdir,'assets','jobs'); // since 2.2.23F2
$create_private_dir($destdir,'assets','module_custom');
$create_private_dir($destdir,'assets','plugins');
$create_private_dir($destdir,'assets','templates');
$create_private_dir($destdir,'assets','themes'); // since 2.2.19
