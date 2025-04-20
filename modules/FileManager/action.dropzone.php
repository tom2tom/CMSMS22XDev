<?php
#FileManager module action
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

if( !function_exists('cmsms') ) exit;
if( !($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed()) ) exit;

$template = 'dropzone.tpl';
if( isset($params['template']) ) {
    $template = trim($params['template']);
    if( !endswith($template,'.tpl') ) $template .= '.tpl'; //TODO end-test was '.tp;' typo?
}
$modname = $this->GetName();
$tpl = $smarty->CreateTemplate($this->GetTemplateResource($template),null,$modname,$smarty);

$tpl->assign('FileManager',$this);
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    $tpl->assign('is_ie',1);
}
$tpl->assign('formstart',$this->CreateFormStart($id,'upload',$returnid,'post','multipart/form-data'));
$tpl->assign('formend',$this->CreateFormEnd());
$post_max_size = filemanager_utils::str_to_bytes(ini_get('post_max_size'));
$upload_max_filesize = filemanager_utils::str_to_bytes(ini_get('upload_max_filesize'));
$tpl->assign('max_chunksize',min($upload_max_filesize,$post_max_size-1024));
$tpl->assign('action_url',$this->create_url('m1_','upload',$returnid));
$tpl->assign('prompt_dropfiles',$this->Lang('prompt_dropfiles'));
$tpl->assign('chdir_url',$this->create_url('m1_','changedir',$returnid)); //TODO &amp; conversion?
$advancedmode = filemanager_utils::check_advanced_mode();

// get a folder list

$cwd = filemanager_utils::get_cwd();
$tpl->assign('cwd',$cwd);

$startdir = $config['uploads_path'];
if( $advancedmode && $this->AdvancedAccessAllowed() ) $startdir = CMS_ROOT_PATH;

// get all directories where we have write permission
$basedir = dirname($startdir);

$get_dirs = function($startdir,$prefix = DIRECTORY_SEPARATOR) use($showhiddenfiles, &$get_dirs) {
    if( !is_dir($startdir) ) return [];
    $res = [];
    $dh = opendir($startdir);
    while( false !== ($entry = readdir($dh)) ) {
        if( $entry == '.' ) continue;
        if( $entry == '..' ) continue;
        $full = filemanager_utils::join_path($startdir,$entry);
        if( !is_dir($full) ) continue;
        if( !is_readable($full) ) continue;
        if( !$showhiddenfiles && filemanager_utils::is_hidden_file($full) ) continue;
        if( $entry == '.svn' || $entry == '.git' ) continue;
        if( is_writable($full) ) $res[$prefix.$entry] = $prefix.$entry;
        $tmp = $get_dirs($full,$prefix.$entry.DIRECTORY_SEPARATOR);
        if( $tmp && is_array($tmp) ) $res = array_merge($res,$tmp);
    }
    closedir($dh);
    return $res;
};

$output = $get_dirs($startdir,DIRECTORY_SEPARATOR.basename($startdir).DIRECTORY_SEPARATOR);
$output[DIRECTORY_SEPARATOR.basename($startdir)] = DIRECTORY_SEPARATOR.basename($startdir);
ksort($output);
$tpl->assign('dirlist',$output);

$tpl->display();
