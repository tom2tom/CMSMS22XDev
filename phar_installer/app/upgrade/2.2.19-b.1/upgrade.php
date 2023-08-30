<?php

//Remove unused nls files
$config = CmsApp::get_instance()->GetConfig();
$patn = cms_join_path($config['admin_path'], 'lang', 'ext', '*.php');
$files = glob($patn, GLOB_NOSORT | GLOB_NOESCAPE);
if( $files ) {
    $tmpl = cms_join_path(CMS_ROOT_PATH, 'lib', 'nls', '%s.nls.php');
    $dp = sprintf($tmpl, 'en_US');
    rename($dp, $dp.'KP'); //preserve it
    foreach( $files as $fp ) {
        $fn = basename($fp, '.php');
        $dp = sprintf($tmpl, $fn);
        if( is_file($dp) ) {
            rename($dp, $dp.'KP'); //preserve it
        }
    }
    //delete all non-preserved files
    $patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'nls', '*.nls.php');
    $files2 = glob($patn, GLOB_NOSORT | GLOB_NOESCAPE);
    foreach( $files2 as $fp ) {
        if( is_file($fp) ) {
            unlink($fp);
        }
    }
    //reinstate all preserved files
    $patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'nls', '*.nls.phpKP');
    $files2 = glob($patn, GLOB_NOSORT | GLOB_NOESCAPE);
    foreach( $files2 as $fp ) {
        $tp = substr($fp, 0, -2);
        rename($fp, $tp);
    }
}
