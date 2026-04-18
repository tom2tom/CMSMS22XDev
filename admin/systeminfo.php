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
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
check_login();

$userid = get_userid();
$access = check_permission($userid, 'Modify Site Preferences');
if (!$access) {
  exit(lang('no_permission')); //TODO throw if can be caught
}

require_once 'header.php';

require_once cms_join_path(dirname(__DIR__), 'lib', 'test.functions.php');

function installerHelpLanguage($lang, $default_null = null)//: string
{
  if( (!is_null($default_null)) && ($default_null == $lang) ) return '';
  return substr($lang, 0, 2);
}

$themeObject->set_value('pagetitle', 'systeminfo');

if(isset($_GET['cleanreport']) && $_GET['cleanreport'] == 1) {
  $tplname = 'systeminfo.txt.tpl';
}
else {
  $tplname  = 'systeminfo.tpl';
}
$tpl = $smarty->createTemplate("admin_tpl:$tplname", null, null, $smarty, false);
$tpl->assign('themename', $themeObject->themeName);
$tpl->assign('backurl', $themeObject->BackUrl());
$tpl->assign('systeminfo_cleanreport', 'systeminfo.php'.$urlext.'&amp;cleanreport=1');

/* Default help url */
$tpl->assign('cms_install_help_url', 'https://docs.cmsmadesimple.org/installation/installing/permissions-and-php-settings');

/* CMS Install Information */

$tpl->assign('cms_version', $CMS_VERSION);

$db = cmsms()->GetDb();
$query = "SELECT * FROM ".CMS_DB_PREFIX."modules WHERE active=1";
$modules = $db->GetArray($query); //TODO any null-valued strings to process?
usort($modules, function($a,$b) { return strcasecmp($a['module_name'], $b['module_name']); });
$tpl->assign('installed_modules', $modules);

clearstatcache();
$tmp = array(0=>array(), 1=>array());

$tmp[0]['php_memory_limit'] = testConfig('php_memory_limit', 'php_memory_limit');
$tmp[1]['debug'] = testConfig('debug', 'debug');

$tmp[0]['max_upload_size'] = testConfig('max_upload_size', 'max_upload_size');
$tmp[0]['url_rewriting'] = testConfig('url_rewriting', 'url_rewriting');
$tmp[0]['page_extension'] = testConfig('page_extension', 'page_extension');
$tmp[0]['query_var'] = testConfig('query_var', 'query_var');

$tmp[1]['root_url'] = testConfig('root_url', 'root_url');
$tmp[1]['ssl_url'] = testConfig('ssl_url', 'ssl_url');
$tmp[1]['root_path'] = testConfig('root_path', 'root_path', 'testDirWrite');
$tmp[1]['uploads_path'] = testConfig('uploads_path', 'uploads_path', 'testDirWrite');
$tmp[1]['uploads_url'] = testConfig('uploads_url', 'uploads_url');
$tmp[1]['image_uploads_path'] = testConfig('image_uploads_path', 'image_uploads_path', 'testDirWrite');
$tmp[1]['image_uploads_url'] = testConfig('image_uploads_url', 'image_uploads_url');
$tmp[1]['ssl_uploads_url'] = testConfig('ssl_uploads_url', 'ssl_uploads_url');
$tmp[1]['themes_path'] = testConfig('themes_path', 'themes_path', 'testDirWrite'); // since 2.2.22F2
$tmp[1]['themes_url'] = testConfig('themes_url', 'themes_url'); // since 2.2.22F2
$tmp[0]['auto_alias_content'] = testConfig('auto_alias_content', 'auto_alias_content');
$tmp[0]['locale'] = testConfig('locale', 'locale');
//$tmp[0]['default_encoding'] = testConfig('default_encoding', 'default_encoding');
//$tmp[0]['admin_encoding'] = testConfig('admin_encoding', 'admin_encoding');
$tmp[0]['set_names'] = testConfig('set_names', 'set_names');
$tmp[0]['timezone'] = testConfig('timezone', 'timezone');
$tmp[0]['permissive_smarty'] = testConfig('permissive_smarty','permissive_smarty');
$tpl->assign('count_config_info', count($tmp[0]));
$tpl->assign('config_info', $tmp);

/* Performance Information */
$tmp = array(0=>array(), 1=>array());

$res = (bool)cms_siteprefs::get('allow_browser_cache', false);
$tmp[0]['allow_browser_cache'] = testBoolean(false, lang('allow_browser_cache'), $res, lang('test_allow_browser_cache'), false);
$res = cms_siteprefs::get('browser_cache_expiry', 60);
$tmp[0]['browser_cache_expiry'] = testRange(false, lang('browser_cache_expiry'), $res, lang('test_browser_cache_expiry'), 1, 60, false);
$phpv = PHP_VERSION_ID;
if( $phpv < 70000 ) {
  if( $phpv >= 50500 ) {
    $opcache = ini_get('opcache.enable');
    $tmp[0]['php_opcache'] = testBoolean(false, lang('php_opcache'), $opcache, '', false, false, 'opcache_enabled');
  }
  else {
    $tmp[0]['php_opcache'] = testBoolean(false, lang('php_opcache'), false, '', false, false, 'opcache_notavailable');
  }
}
$res = (bool)cms_siteprefs::get('use_smartycache', 0);
$tmp[0]['smarty_cache'] = testBoolean(false, lang('prompt_use_smartycaching'), $res, lang('test_smarty_caching'), false);
$res = (bool)cms_siteprefs::get('use_smartycompilecheck', false);
$tmp[0]['smarty_compilecheck'] = testBoolean(false, lang('prompt_smarty_compilecheck'), $res, lang('test_smarty_compiled'), false, true);
$res = cms_siteprefs::get('auto_clear_cache_age', 0);
$tmp[0]['auto_clear_cache_age'] = testBoolean(false, lang('autoclearcache2'), $res, lang('test_auto_clear_cache_age'), false);

$tpl->assign('performance_info', $tmp);


/* PHP Information */

$tmp = array(0=>array(), 1=>array());

$session_save_path = ini_get('session.save_path');
$open_basedir = ini_get('open_basedir');

list($minimum, $recommended) = getTestValues('php_version');
$tmp[0]['phpversion'] = testVersionRange(false, 'phpversion', PHP_VERSION, '', $minimum, $recommended, false);

$tmp[0]['md5_function'] = testBoolean(false, 'md5_function', function_exists('md5'), '', false, false, 'Function_md5_disabled');
$tmp[0]['json_function'] = testBoolean(false, 'json_function', function_exists('json_decode'), '', false, false, 'json_disabled');

list($minimum, $recommended) = getTestValues('gd_version');
$tmp[0]['gd_version'] = testGDVersion(false, 'gd_version', $minimum, '', 'min_GD_version');

$tmp[0]['tempnam_function'] = testBoolean(false, 'tempnam_function', function_exists('tempnam'), '', false, false, 'Function_tempnam_disabled');

$tmp[0]['magic_quotes_runtime'] = testBoolean(false, 'magic_quotes_runtime', 'magic_quotes_runtime', lang('magic_quotes_runtime_on'), true, true, 'magic_quotes_runtime_On');
$tmp[0]['E_ALL'] = testIntegerMask(false,lang('test_error_eall'), 'error_reporting',E_ALL,lang('test_eall_failed'),true,false,false);
if( $phpv < 80400 ) { //E_STRICT deprecated and useless since PHP 8.4
  $tmp[0]['E_STRICT'] = testIntegerMask(false,lang('test_error_estrict'), 'error_reporting',E_STRICT,lang('test_estrict_failed'),true,true,false);
}
if( defined('E_DEPRECATED') ) {
  $tmp[0]['E_DEPRECATED'] = testIntegerMask(false,lang('test_error_edeprecated'), 'error_reporting',E_DEPRECATED,lang('test_edeprecated_failed'),true,true,false);
}

$_tmp = _testTimeSettings1();
$tmp[0]['test_file_timedifference'] = ($_tmp->value) ? testDummy('test_file_timedifference',lang('msg_notimedifference2'),'green') : testDummy('test_file_timedifference',lang('error_timedifference2'),'red');
$_tmp = _testTimeSettings2();
$tmp[0]['test_db_timedifference'] = ($_tmp->value) ? testDummy('test_db_timedifference',lang('msg_notimedifference2'),'green') : testDummy('test_file_timedifference',lang('error_timedifference2'),'red');

$tmp[0]['create_dir_and_file'] = testCreateDirAndFile(false, '', '');

list($minimum, $recommended) = getTestValues('memory_limit');
$tmp[0]['memory_limit'] = testRange(false, 'memory_limit', 'memory_limit', '', $minimum, $recommended, true, true, -1, 'memory_limit_range');

list($minimum, $recommended) = getTestValues('max_execution_time');
$tmp[0]['max_execution_time'] = testRange(false, 'max_execution_time', 'max_execution_time', '', $minimum, $recommended, true, false, 0, 'max_execution_time_range');

$tmp[0]['register_globals'] = testBoolean(false, lang('register_globals'), 'register_globals', '', true, true, 'register_globals_enabled');

$ob = ini_get('output_buffering');
if( strtolower($ob) == 'off' || strtolower($ob) == 'on' ) {
  $tmp[0]['output_buffering'] = testBoolean(false, lang('output_buffering'), 'output_buffering', '', true, false, 'output_buffering_disabled');
}
else {
  $tmp[0]['output_buffering'] = testInteger(false, lang('output_buffering'), 'output_buffering', '', true, true, 'output_buffering_disabled');
}

$tmp[0]['disable_functions'] = testString(false, lang('disable_functions'), 'disable_functions', '', true, 'green', 'yellow', 'disable_functions_not_empty');

$tmp[0]['open_basedir'] = testString(false, lang('open_basedir'), $open_basedir, '', false, 'green', 'yellow', 'open_basedir_enabled');

$tmp[0]['test_remote_url'] = testRemoteFile(false, 'test_remote_url', '', lang('test_remote_url_failed'));

$tmp[0]['file_uploads'] = testBoolean(false, 'file_uploads', 'file_uploads', '', true, false, 'Function_file_uploads_disabled');

list($minimum, $recommended) = getTestValues('post_max_size');
$tmp[0]['post_max_size'] = testRange(false, 'post_max_size', 'post_max_size', '', $minimum, $recommended, true, true, null, 'min_post_max_size');

list($minimum, $recommended) = getTestValues('upload_max_filesize');
$tmp[0]['upload_max_filesize'] = testRange(false, 'upload_max_filesize', 'upload_max_filesize', '', $minimum, $recommended, true, true, null, 'min_upload_max_filesize');

$session_save_path = testSessionSavePath('');
if( empty($session_save_path) ) {
  $tmp[0]['session_save_path'] = testDummy('session_save_path', lang('os_session_save_path'), 'yellow', '', 'session_save_path_empty', '');
}
elseif( !empty($open_basedir) ) {
  $tmp[0]['session_save_path'] = testDummy('session_save_path', lang('open_basedir_active'), 'yellow', '', 'No_check_session_save_path_with_open_basedir', '');
}
else {
  $tmp[0]['session_save_path'] = testDirWrite(false, lang('session_save_path'), $session_save_path, $session_save_path, 1);
}
$tmp[0]['session_use_cookies'] = testBoolean(false, 'session.use_cookies', 'session.use_cookies');

$tmp[0]['xml_function'] = testBoolean(true, 'xml_function', extension_loaded_or('xml'), '', false, false, 'Function_xml_disabled');
$tmp[0]['xmlreader_class'] = testBoolean(true, 'xmlreader_class', class_exists('XMLReader',false), '', false, false, 'class_xmlreader_unavailable');

#$tmp[1]['file_get_contents'] = testBoolean(false, 'file_get_contents', function_exists('file_get_contents'), '', false, false, 'Function_file_get_content_disabled');

$_log_errors_max_len = (ini_get('log_errors_max_len')) ? ini_get('log_errors_max_len').'0' : '99';
ini_set('log_errors_max_len', $_log_errors_max_len);
$result = (ini_get('log_errors_max_len') == $_log_errors_max_len);
$tmp[0]['check_ini_set'] = testBoolean(false, 'check_ini_set', $result, lang('check_ini_set_off'), false, false, 'ini_set_disabled');

$hascurl = 0;
$curlgood = 0;
$curl_version = '';
$min_curlversion = '7.19.7';
if( in_array('curl',get_loaded_extensions()) ) {
  $hascurl = 1;
  if( function_exists('curl_version') ) {
    $t = curl_version();
    if( isset($t['version']) ) {
      $curl_version = $t['version'];
      if( version_compare($t['version'],$min_curlversion) >= 0 ) {
        $curlgood = 1;
      }
    }
  }
}
if( !$hascurl ) {
  $tmp[0]['curl'] = testDummy('curl',lang('off'),'yellow','','curl_not_available','');
}
else {
  $tmp[0]['curl'] = testDummy('curl',lang('on'),'green');
  if( $curlgood ) {
    $tmp[1]['curlversion'] = testDummy('curlversion',
                             lang('curl_versionstr',$curl_version,$min_curlversion),
                             'green');
  }
  else {
    $tmp[1]['curlversion'] = testDummy('curlversion',lang('curlversion'),'yellow',
                             lang('curl_versionstr',$curl_version,$min_curlversion));
  }
}
$tpl->assign('count_php_information', count($tmp[0]));
$tpl->assign('php_information', $tmp);


/* Server Information */

$tmp = array(0=>array(), 1=>array());

$tmp[0]['server_software'] = testDummy('', $_SERVER['SERVER_SOFTWARE'], '');
$tmp[0]['server_api'] = testDummy('', PHP_SAPI, '');
if( function_exists('php_uname') ) {
  $tmp[0]['server_os'] = testDummy('', PHP_OS . ' ' . php_uname('r') .' '. lang('on') .' '. php_uname('m'), ''); // NOTE PHP_OS is the build-system
}
else {
  $tmp[0]['server_os'] = 'Unknown'; // TODO fallack mechanism
}

switch($config['dbms']) { //workaround: ServerInfo() is unsupported in adodblite and CMSMS Connection
  case 'mysqli':
  case 'mysql':
    $v = $db->GetOne('SELECT version()');
    $tmp[0]['server_db_type'] = testDummy('', 'MySQL ('.$config['dbms'].')', '');
    $_server_db = (false === strpos($v, "-")) ? $v : substr($v, 0, strpos($v, "-"));
    list($minimum, $recommended) = getTestValues('mysql_version');
    $tmp[0]['server_db_version'] = testVersionRange(false, 'server_db_version', $_server_db, '', $minimum, $recommended, false);

    $grants = $db->GetArray('SHOW GRANTS FOR CURRENT_USER');
    if( !is_array($grants) || count($grants) == 0 ) {
      $tmp[0]['server_db_grants'] = testDummy('db_grants',lang('os_db_grants'),'yellow','','error_no_grantall_info');
    }
    else {
      $found_grantall = false;
      function __check_grant_all($item,$key)
      {
        global $found_grantall;
        if( stripos($item,'GRANT ALL PRIVILEGES') !== false ) {
          $found_grantall = true;
        }
      }
      array_walk_recursive($grants,'__check_grant_all');
      if( !$found_grantall ) {
        $tmp[0]['server_db_grants'] = testDummy('db_grants',lang('error_nograntall_found'),'yellow');
      }
      else {
        $tmp[0]['server_db_grants'] = testDummy('db_grants',lang('msg_grantall_found'),'green');
      }
    }
    break;
}

$tpl->assign('count_server_info', count($tmp[0]));
$tpl->assign('server_info', $tmp);

$tmp = array(0=>array(), 1=>array());

$dir = CMS_ROOT_PATH . DIRECTORY_SEPARATOR . 'tmp';
$tmp[0]['tmp'] = testDirWrite(false, $dir, $dir);

$dir = TMP_CACHE_LOCATION;
$tmp[0]['tmp_cache'] = testDirWrite(false, $dir, $dir);

$dir = TMP_CONFIG_LOCATION;
$tmp[0]['tmp_config'] = testDirWrite(false, $dir, $dir);

$dir = TMP_TEMPLATES_C_LOCATION;
$tmp[0]['templates_c'] = testDirWrite(false, $dir, $dir);

$dir = CMS_ROOT_PATH . DIRECTORY_SEPARATOR . 'modules';
$tmp[0]['modules'] = testDirWrite(false, $dir, $dir);

$dir = $config['uploads_path'];
$tmp[0]['uploads'] = testDirWrite(false, $dir, $dir);

// deprecated since 2.2.19 Avoid using umask() in multithreaded webservers, all running scripts use the same umask
$global_umask = cms_siteprefs::get('global_umask', '022');
$tmp[0][lang('global_umask')] = testUmask(false, lang('global_umask'), $global_umask);

$result = is_writable(CONFIG_FILE_LOCATION);
#$tmp[1]['config_file'] = testFileWritable(false, lang('config_writable'), CONFIG_FILE_LOCATION, '');
$tmp[0]['config_file'] = testDummy('', substr(sprintf('%o', fileperms(CONFIG_FILE_LOCATION)), -4), (($result) ? 'red' : 'green'), (($result) ? lang('config_writable') : ''));

$tpl->assign('count_permission_info', count($tmp[0]));
$tpl->assign('permission_info', $tmp);

if(isset($_GET['cleanreport']) && $_GET['cleanreport'] == 1) {
  $orig_lang = CmsNlsOperations::get_current_language();
  CmsNlsOperations::set_language('en_US');
  $tpl->display();
  CmsNlsOperations::set_language($orig_lang);
}
else {
  $tpl->display();
}
require_once 'footer.php';
