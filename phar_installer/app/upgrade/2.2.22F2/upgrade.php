<?php

$config = CmsApp::get_instance()->GetConfig(); // OR cms_config::get_instance();
$bt = $config['themes_path'];
if( !$bt ) return;
if( !is_dir($bt) ) {
	@mkdir($bt,0777,true);// should be done during Files step
	touch($bt.DIRECTORY_SEPARATOR.'index.html');
}
$bf = $config['uploads_path'];
if( is_dir($bf) ) {
	//redundant file
	$fp = cms_join_path($bf,'images','thumb_logo1.gif');
	if( is_file($fp) ) {
		unlink($fp);
	}
	//demo-site themes recursive move (aka rename but watch for links!) $bf to $bt done during Files step
	// detect & process installed non-demo-content theme(s) if any, ignore any designs
	$modnames = $db->GetCol('SELECT module_name FROM '.CMS_DB_PREFIX.'modules');
	$dirs = glob($bf.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT | GLOB_ONLYDIR);
	foreach( $dirs as $dp ) {
		$name = basename($dp);
		switch ($name) {
			case 'images':
			break;
			case 'designs': // merged into themes-place during DesignManager 1.1.2 upgrade
			break;
			default:
			if( $modnames && in_array($name, $modnames) ) break;
			if( 1 ) break; //TODO if $dp seems a non-theme place e.g. empty ...
			rename($dp, $bt.DIRECTORY_SEPARATOR.$name); //might fail and warn
			break;
		}
	}
}

// notionally, 'designs'-place corrections would be done in DM 1.1.2 upgrade
// but in that case, themes_root/designs etc reversions would be needed during the following
//TODO [[root_url]]/uploads/designs also in the following?
//$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"[[uploads_url]]/designs","[[assets_root]]/designs")'; for a distinct 'designs' tree
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"[[uploads_url]]/designs","[[themes_root]]")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_stylesheets SET content=REPLACE(REPLACE(content,"[[uploads_url]]","[[themes_root]]"),"[[root_url]]/uploads","[[themes_root]]")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_stylesheets SET content=REPLACE(content,"[[themes_root]]/images","[[uploads_url]]/images")';
$db->Execute($sql);
//TODO {root_url}/uploads/designs also?
//$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"{uploads_url}/designs","{assets_root}/designs")'; for a distinct 'designs' tree
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"{uploads_url}/designs","{themes_root}")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(REPLACE(content,"{uploads_url}","{themes_root}"),"{root_url}/uploads","{themes_root}")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"{themes_root}/images","{uploads_url}/images")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"uploads/","{themes_root rel=1}/")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_templates SET content=REPLACE(content,"{themes_root rel=1}/images","uploads/images")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_designs SET description=REPLACE(description,"uploads/","assets/themes/")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'layout_designs SET description=REPLACE(description,"assets/themes/images","uploads/images")';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'content_props SET content=REPLACE(content,"uploads/","assets/themes/") WHERE prop_name=\'content_en\'';
$db->Execute($sql);
$sql = 'UPDATE '.CMS_DB_PREFIX.'content_props SET content=REPLACE(content,"assets/themes/images","uploads/images") WHERE prop_name=\'content_en\'';
//OTHERS PROB. OK e.g. NOT } ]] (/)? uploads NOT /images content_properties etc? {themes_root rel=1}
