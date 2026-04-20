<?php
#CMS Made Simple script: provide urls and language strings for use in admin js
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
#along with this program; if not, read the license online at
#https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

$nowlang = CmsNlsOperations::get_current_language();
$region = ($nowlang == '') ? 'US' : substr($nowlang,3);
$secureval = $_SESSION[CMS_USER_KEY];
$sufx = hash('fnv1a64',$secureval.$region);
$filepath = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR . "runtime_{$region}$sufx.js";
$runtimeurl = str_replace([CMS_ROOT_PATH,'\\'],[CMS_ROOT_URL,'/'],$filepath); // for upstream use
if( is_file($filepath) ) {
	return; // no need to recreate it
}
else {
	// delete any other cached files named like runtime_{$region}*.js
	$oldpath = str_replace($sufx,'*',$filepath);
	$all = glob($oldpath);
	foreach( $all as $oldone ) {
		unlink($oldone);
	}
}

$picker = ModuleOperations::get_instance()->GetFilePickerModule();
if( $picker ) {
	$value = $picker->get_browser_url();
	$pickurl = str_replace('&amp;','&',$value).'&showtemplate=false';
}
else {
	$pickurl = '';
}
$securename = CMS_SECURE_PARAM_NAME;

$data = [];
$data['admin_url'] = $config['admin_url'];
$data['ajax_alerts_url'] = "ajax_alerts.php?$securename=$secureval"; //admin-relative url ok?
$data['ajax_help_url'] = "ajax_help.php?$securename=$secureval";
$data['filepicker_url'] = $pickurl;
$data['lang_alert'] = lang('alert');
$data['lang_cancel'] = lang('cancel');
$data['lang_choose'] = lang('choose');
$data['lang_close'] = lang('close');
$data['lang_confirm'] = lang('confirm');
$data['lang_disabled'] = lang('disabled');
$data['lang_error'] = lang('error');
$data['lang_filetobig'] = lang('upload_filetobig');
$data['lang_hierselect_title'] = lang('title_hierselect_select');
$data['lang_largeupload'] = lang('upload_largeupload');
$data['lang_no'] = lang('no');
$data['lang_none'] = lang('none');
$data['lang_ok'] = lang('ok');
$data['lang_select_file'] = lang('select_file');
$data['lang_title_help'] = lang('help');
$data['lang_yes'] = lang('yes');
$data['max_upload_size'] = $config['max_upload_size'];
$data['noticetimeout'] = (int)cms_siteprefs::get('notices_timeout',10); //seconds or falsy
$data['root_url'] = CMS_ROOT_URL;
$data['secure_param_name'] = $securename;
$data['secure_param_value'] = $secureval;
$data['themes_url'] = $config['themes_url'];
$data['uploads_url'] = $config['uploads_url'];
$data['user_key'] = $secureval; //deprecated since 2.2.23F2 use 'secure_param_value'

// see Smarty escape|'javascript'
// see https://html.spec.whatwg.org/multipage/scripting.html#restrictions-for-contents-of-script-elements
$escapes = [
	'\\' => '\\\\',
	"'"  => "\\'",
	"\r" => '\\r',
	"\n" => '\\n',
	"`" => "\\\\`",
	'</' => '<\/'
];
// generate the javascript
$out = "var cms_data = {\n";
foreach( $data as $key => $value ) {
	$jsval = (is_string($value)) ? "'".strtr($value,$escapes)."'" : $value;
	$out .= " $key:$jsval,\n";
}
$out = rtrim($out, ",\n") . "\n};\n";

file_put_contents($filepath,$out);
