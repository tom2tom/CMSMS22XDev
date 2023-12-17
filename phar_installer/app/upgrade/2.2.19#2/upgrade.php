<?php

$config = CmsApp::get_instance()->GetConfig();
$dir = cms_join_path($config['assets_path'],'themes');
if( !is_dir($dir) ) {
    @mkdir($dir,0777,true);
}
touch($dir.DIRECTORY_SEPARATOR.'index.html');
