<?php

$config = CmsApp::get_instance()->GetConfig();
$dir = cms_join_path($config['admin_path'],'configs');
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);//OR 0775 or 0770
}
touch($dir.DIRECTORY_SEPARATOR.'index.html');

$dir = cms_join_path($config['assets_path'],'configs');
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);//OR 0775 or 0770
}
touch($dir.DIRECTORY_SEPARATOR.'index.html');
