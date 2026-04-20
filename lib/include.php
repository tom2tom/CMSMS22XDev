<?php
#CMS Made Simple initialization script
#(c) 2011 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

/**
 * This file is included in every request.  It does all setup functions including
 * importing additional functions/classes, setting up sessions and nls, and
 * assigning various important variables like $gCms.
 *
 * This script is not for use by third party applications to create access to
 * CMSMS API's.  It is intended for and supported for use in CMSMS applications only.
 *
 * @package CMS
 */

/**
 * Global variables that might be set before this file is included and which will influence its behavior.
 *
 * DONT_LOAD_DB       - Indicates that the database should not be initialized and any database related functions should not be called
 * DONT_LOAD_SMARTY   - Indicates that smarty should not be initialized, and no smarty related variables assigned.
 * CMS_INSTALL_PAGE   - Indicates that the file was included from the CMSMS Installation/Upgrade process
 * CMS_PHAR_INSTALLER - UNUSED HERE Indicates that the file was included from the CMSMS PHAR based installer (note: CMS_INSTALL_PAGE will also be set).
 * CMS_ADMIN_PAGE     - Indicates that the file was included from an admin side request.
 * CMS_LOGIN_PAGE     - Indicates that the file was included from the admin login form.
 */

define('CMS_DEFAULT_VERSIONCHECK_URL', 'https://www.cmsmadesimple.org/latest_version.php'); // (used once/day)
define('CMS_SECURE_PARAM_NAME', '__c'); // used for CSRF protection (first-needed in login.php)
define('CMS_USER_KEY', '_userkey_'); // used for CSRF protection (first-needed in misc.functions)
if (!defined('CONFIG_FILE_LOCATION')) {
    define('CONFIG_FILE_LOCATION', dirname(__DIR__) . '/lib/config.php');
}
global $CMS_INSTALL_PAGE, $CMS_ADMIN_PAGE, $CMS_LOGIN_PAGE, $DONT_LOAD_DB, $DONT_LOAD_SMARTY;

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}

if (!isset($CMS_INSTALL_PAGE) && (!file_exists(CONFIG_FILE_LOCATION) || filesize(CONFIG_FILE_LOCATION) < 100)) {
    die ('FATAL ERROR: config.php file not found or invalid');
}

// sanitize $_SERVER and $_GET
// FILTER_SANITIZE_STRING bound to be removed on PHP 9
//$_SERVER = filter_var_array($_SERVER, FILTER_SANITIZE_STRING);
//$_GET = filter_var_array($_GET, FILTER_SANITIZE_STRING);

/**
 * replacement for the formerly-used, deprecated, filter_var_array FILTER_SANITIZE_STRING
 * temporary as we will revisit the security measures used
 * (JoMorg)
 * input-sanitizing is best when context-specific and tailored accordingly,
 * and the original $_SERVER, $_GET values are still available
 *
 * Note: the closure is recursive to allow for parameters with arrays
 * removal of unclosed PHP tags ('/<\?php.*$/i','/<\?=.*$/') is a crasher ?
 *
 * @param $param
 *
 * @return array|string
 */
$sanitize_fn = function (&$param) use (&$sanitize_fn)
{
  if( is_array($param) ) {
    array_walk($param, $sanitize_fn);
  }
  else {
    $tmp = preg_replace(['/<[^>]*>/', '/(<|%3c)(\?|%3f)php.*$/i', '/(<|%3c)(\?|%3f)=?.*$/i'], ['', '', ''], $param);
    return strtr($tmp, ["\0"=>'', "'"=>'&#39;', '"'=>'&#34;']);
  }
};
array_walk($_SERVER, $sanitize_fn);
array_walk($_GET, $sanitize_fn);

// include some stuff
$dirname = __DIR__ . DIRECTORY_SEPARATOR;
require_once $dirname . 'compat.functions.php';
require_once $dirname . 'misc.functions.php';
require_once $dirname . 'version.php'; // tells us where the config file is and other things.
require_once $dirname . 'classes' . DIRECTORY_SEPARATOR . 'class.CmsException.php';
require_once $dirname . 'classes' . DIRECTORY_SEPARATOR . 'class.HookManager.php';
require_once $dirname . 'classes' . DIRECTORY_SEPARATOR . 'class.cms_config.php';
require_once $dirname . 'classes' . DIRECTORY_SEPARATOR . 'class.CmsApp.php';
require_once $dirname . 'classes' . DIRECTORY_SEPARATOR . 'class.JobCheck.php';
require_once $dirname . 'autoloader.php';
require_once $dirname . 'module.functions.php';
require_once $dirname . 'page.functions.php';
require_once $dirname . 'content.functions.php';
require_once $dirname . 'translation.functions.php';
require_once $dirname . 'html_entity_decode_php4.php';

debug_buffer('done loading basic files');

//Grab the current configuration
$_app = CmsApp::get_instance(); // for use in this file only.
$config = $_app->GetConfig();

if( $config['debug'] ) { // OR CMS_DEBUG
    @ini_set('display_errors',1);
    @error_reporting(E_ALL);
}

if( cms_to_bool(ini_get('register_globals')) ) {
    echo 'FATAL ERROR: For security reasons register_globals must not be enabled for any CMSMS install.  Please adjust your PHP configuration settings to disable this feature.';
    die;
}

if( isset($CMS_ADMIN_PAGE) ) {
    setup_session();

    function cms_admin_sendheaders($content_type = 'text/html',$charset = '') {
        if( !headers_sent() ) {
            // Language shizzle
            if( !$charset ) $charset = get_encoding();
            header("Content-Type: $content_type; charset=$charset");
        }
    }
}

require_once $dirname . 'std_hooks.php';

// new for 2.0 ... this creates a mechanism whereby items can be cached automatically, and fetched (or calculated) via the use of a callback
// if the cache is too old, or the cached value has been cleared or not yet been saved.
$obj = new \CMSMS\internal\global_cachable('schema_version',
               function() {
                   $db = \CmsApp::get_instance()->GetDb();
                   $query = 'SELECT version FROM '.CMS_DB_PREFIX.'version';
                   return $db->GetOne($query);
               });
\CMSMS\internal\global_cache::add_cachable($obj);
$obj = new \CMSMS\internal\global_cachable('latest_content_modification',
               function() {
                   $db = \CmsApp::get_instance()->GetDb();
                   $query = 'SELECT modified_date FROM '.CMS_DB_PREFIX.'content ORDER BY modified_date DESC';
                   $tmp = $db->GetOne($query);
                   return $db->UnixTimeStamp($tmp);
               });
\CMSMS\internal\global_cache::add_cachable($obj);
$obj = new \CMSMS\internal\global_cachable('default_content',
               function() {
                   $db = \CmsApp::get_instance()->GetDb();
                   $query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
                   return (int)$db->GetOne($query); // 0 if not found
               });
\CMSMS\internal\global_cache::add_cachable($obj);
$obj = new \CMSMS\internal\global_cachable('modules',
               function() {
                   $db = \CmsApp::get_instance()->GetDb();
                   $query = 'SELECT * FROM '.CMS_DB_PREFIX.'modules ORDER BY module_name';
                   return $db->GetArray($query);
               });
\CMSMS\internal\global_cache::add_cachable($obj);
$obj = new \CMSMS\internal\global_cachable('module_deps',
               function() {
                   $db = \CmsApp::get_instance()->GetDb();
                   $query = 'SELECT parent_module,child_module,minimum_version FROM '.CMS_DB_PREFIX.'module_deps ORDER BY parent_module';
                   $tmp = $db->GetArray($query);
                   if( !is_array($tmp) || !count($tmp) ) return [];
                   $out = array();
                   foreach( $tmp as $row ) {
                       $out[$row['child_module']][$row['parent_module']] = $row['minimum_version'];
                   }
                   return $out;
               });
\CMSMS\internal\global_cache::add_cachable($obj);
cms_siteprefs::setup();
Events::setup();
UserTagOperations::setup();
ContentOperations::setup_cache();

// Set the timezone
if( $config['timezone'] ) @date_default_timezone_set(trim($config['timezone']));

// Attempt to override the php memory limit
if( isset($config['php_memory_limit']) && !empty($config['php_memory_limit'])  ) ini_set('memory_limit',trim($config['php_memory_limit']));

// Load them into the usual variables.  This'll go away a little later on.
if( !isset($DONT_LOAD_DB) ) {
    try {
        debug_buffer('Initialize Database');
        $_app->GetDb();
        debug_buffer('Done Initializing Database');
    }
    catch( \CMSMS\Database\DatabaseConnectionException $e ) {
        die('Sorry, something has gone wrong.  Please contact a site administrator. <em>('.get_class($e).')</em>');
    }
}

//Fix for IIS (and others) to make sure REQUEST_URI is filled in
if( !isset($_SERVER['REQUEST_URI']) ) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    if(isset($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
}

if( !isset($CMS_INSTALL_PAGE) ) {
    // Set a umask
    // BUT avoid using umask() in multithreaded webservers. All running scripts use the same umask
    // Deprecated since 2.2.19
    $global_umask = cms_siteprefs::get('global_umask');
    if( $global_umask !== '' ) umask(octdec($global_umask));

    // Load all eligible modules
    debug_buffer('Loading Modules');
    $modops = ModuleOperations::get_instance();
    $modops->LoadModules(!isset($CMS_ADMIN_PAGE));
    debug_buffer('End of Loading Modules');
}

//Setup language stuff.... will auto-detect languages (Launch only to admin at this point)
if( isset($CMS_ADMIN_PAGE) ) CmsNlsOperations::set_language();

if( !isset($DONT_LOAD_SMARTY) ) {
    if( CMS_DEBUG ) { //OR $config['debug']
        debug_buffer('Initialize Smarty');
        $smarty = $_app->GetSmarty();
        $smarty->error_reporting = E_ALL;
        debug_buffer('Done Initializing Smarty');
    }
    else {
        $smarty = $_app->GetSmarty();
    }

    static $lang = null;
    if( $lang === null ) {
        // once per request
        $smarty->assign('sitename', cms_siteprefs::get('sitename', 'CMSMS Site'));

        $lang = '';
        $ldir = 'ltr';
        $sside = 'left';
        $eside = 'right';
        CmsNlsOperations::set_language(); // <- NLS detection for frontend
        $tmp = CmsNlsOperations::get_current_language();
        if( $tmp ) {
            $lang = CmsNlsOperations::get_lang_attribute($tmp);
            $info = CmsNlsOperations::get_language_info($tmp);
            if( $info ) {
                $ldir = ($info->direction()) ?: 'ltr';
                list($sside,$eside) = ($ldir == 'ltr') ? ['left','right'] : ['right','left'];
            }
        }
        $smarty->assign('lang', $lang, true)
         ->assign('lang_dir', $ldir)
         ->assign('stside', $sside)
         ->assign('ndside', $eside);
    }
}

//bad hack! TODO deploy a better solution for onetime init outside of installer
if( !isset($CMS_INSTALL_PAGE) ) {
    try {
        Events::AddEventTypedHandler('Core', 'ModuleUninstalled', 'CMSMS\JobOperations::clear_module', Events::HANDLERCALL, false);
    }
    catch(Exception $e) {
        // nothing here
    }
}
