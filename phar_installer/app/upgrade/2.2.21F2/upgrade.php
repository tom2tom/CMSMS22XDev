<?php

$config = CmsApp::get_instance()->GetConfig();
$dir = cms_join_path($config['admin_path'],'configs');
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);
    touch($dir.DIRECTORY_SEPARATOR.'index.html');
}

$dir = cms_join_path($config['assets_path'],'configs');
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);
    touch($dir.DIRECTORY_SEPARATOR.'index.html');
}

$dir = cms_join_path(CMS_ROOT_PATH,'tmp');
$fp = $dir.DIRECTORY_SEPARATOR.'index.html';
if ( !is_file($fp) ) touch($fp);

$dir .= DIRECTORY_SEPARATOR.'config';
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);
    touch($dir.DIRECTORY_SEPARATOR.'index.html');
}
