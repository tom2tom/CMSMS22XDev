<?php

cms_admin_sendheaders();
$starttime = microtime();
if (!(isset($USE_OUTPUT_BUFFERING) && $USE_OUTPUT_BUFFERING == false)) @ob_start();

$userid = get_userid();
$smarty = Smarty_CMS::get_instance();

if (isset($USE_THEME) && $USE_THEME == false) {
    //echo '<!-- admin theme disabled -->';
}
else {
    debug_buffer('before theme load');
    $themeObject = cms_utils::get_theme_object();
    debug_buffer('after theme load');
    $smarty->assign('secureparam',CMS_SECURE_PARAM_NAME . '=' . $_SESSION[CMS_USER_KEY],true); //also in adminthemebase for some requests

    // Display notification stuff from modules
    // should be controlled by preferences or something
//  $ignoredmodules = explode(',',cms_userprefs::get_for_user($userid,'ignoredmodules')); no such thing

    $sitedown_file = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR . 'SITEDOWN';
    if (file_exists($sitedown_file)) {
        if (cms_siteprefs::get('enablenotifications',true) && cms_userprefs::get_for_user($userid,'enablenotifications',true)) {
            // Display a warning
            $sitedown_message = lang('sitedownwarning',$sitedown_file);
            $themeObject->AddNotification(1,'Core',$sitedown_message);
        }
    }
    else {
        $smarty->changeCaching(true); // by default, non-module admin pages support caching
    }
    $themeObject->do_header();
}
