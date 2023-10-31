<?php
#Module FilePicker action
#(c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
#(c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOpUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

use CMSMS\FilePickerProfile;
use CMSMS\FileType;
use FilePicker\PathAssistant;
use FilePicker\Profile;
use FilePicker\TemporaryProfileStorage;

if( !isset($gCms) ) exit;
if( !check_login(FALSE) ) exit; // admin only.... but any admin

//$handlers = ob_list_handlers();
//for ($cnt = 0; $cnt < count($handlers); $cnt++) { ob_end_clean(); }

$clean_str = function($in) {
    if( $in ) {
        $in = cleanValue($in);
        $in = strip_tags($in);
        return trim($in);
    }
    return (string)$in;
};

$sortfiles = function($file1,$file2) {
    if ($file1['isdir'] && !$file2['isdir']) return -1;
    if (!$file1['isdir'] && $file2['isdir']) return 1;
    return strnatcasecmp($file1['name'],$file2['name']);
};

$get_thumbnail_tag = function($file,$path,$url) {
    $imagetag = '';
    $imagepath = $path.DIRECTORY_SEPARATOR.'thumb_'.$file;
    if( is_file($imagepath) ) {
        $imageurl = $url.'/thumb_'.$file;
        $imagetag = '<img src="'.$imageurl.'" alt="'.$file.'" title="'.$file.'">';
    }
    return $imagetag;
};

$accept_file = function(Profile $profile,$assistant,$path,$filename) {
    if( $filename == '.' ) {
        return FALSE;
    }
    $fullpath = cms_join_path($path,$filename);
    if( $filename == '..' || $this->is_acceptable_filename($profile,$filename) ) {
        return $assistant->is_relative($fullpath);
    }
    return FALSE;
};

/*
In principle, the request-parameters might include
CMS_SECURE_PARAM_NAME,'mact','showtemplate',
'_enc','inst','subdir','nosub','sig','useprefix'
and/or none|some|all profile properties, some of which may override corresponding current property value:
'id'(RO),'name'(RO),'create_date'(RO),'modified_date'(?),'file_extensions','prefix',
'top'(RO),'type','can_upload','can_mkdir','can_delete','show_thumbs','show_hidden',
'sort','match_prefix','exclude_prefix'
*/
//
// initialization
//
if( isset($params['_enc']) ) {
    $diropts = json_decode(base64_decode($params['_enc']),TRUE);
    unset($params['_enc']);
    if( $diropts && is_array($diropts) ) {
        $params = array_merge($params,$diropts);
        $params['useprefix'] = true;
    }
}

$inst = isset($params['inst']) ? $clean_str($params['inst']) : '';
$nosub = isset($params['nosub']) ? cms_to_bool($params['nosub']) : false;
$sig = isset($params['sig']) ? $clean_str($params['sig']) : '';
$profile = ( $sig ) ? TemporaryProfileStorage::get($sig) : null; // no object
if( !$profile ) $profile = $this->get_default_profile();
if( $profile && !$sig ) { //CHECKME
    $sig = TemporaryProfileStorage::set($profile);
}

// tailor the profile
$custom = [];
if( !$this->CheckPermission('Modify Files') ) { //TODO is this the relevant perm for 'normal' uploading?
    $custom = [
     'can_upload'=>FilePickerProfile::FLAG_NONE,
     'can_delete'=>FilePickerProfile::FLAG_NONE,
     'can_mkdir'=>FilePickerProfile::FLAG_NONE];
}
$type = isset($params['type']) ? $clean_str($params['type']) : FileType::TYPE_ANY;
$custom['type'] = $type; //profile type may be unchanged
$profile2 = $profile->overrideWith($custom); //CHECKME replacement object cached anywhere? OK?

// get our absolute top directory
$topdir = $profile2->top;
if( !$topdir ) $topdir = $config['uploads_path'];
$assistant = new PathAssistant($config,$topdir);

// get our current working directory relative to $topdir
// try cwd stored in session first... then if necessary the profile topdir, then if necessary, the absolute topdir
$sesskey = md5(__FILE__);
$cwd = '';
if( isset($_SESSION[$sesskey]) ) {
    $cwd = trim($_SESSION[$sesskey]);
}
if( !$cwd && $profile2->top ) {
    $cwd = $assistant->to_relative($profile2->top);
}
if( !$nosub && isset($params['subdir']) ) {
    try {
        $cwd .= DIRECTORY_SEPARATOR . cms_html_entity_decode(trim($params['subdir']));
        $cwd = $assistant->to_relative($assistant->to_absolute($cwd));
    }
    catch( Exception $e ) {
        // ignore
    }
}
// failsafe - if we don't have a valid working directory, set it to the $topdir
if( $cwd && !$assistant->is_valid_relative_path( $cwd ) ) {
    $cwd = '';
}
//if( $cwd ) $_SESSION[$sesskey] = $cwd;
$_SESSION[$sesskey] = $cwd;

$starturl = $assistant->relative_path_to_url($cwd);
$startdir = $assistant->to_absolute($cwd);

if( isset($params['useprefix']) && cms_to_bool($params['useprefix']) ) {
    $fullname = cms_join_path($startdir,'XAZ');//fake/placeholder hence realpath() in Assistant N/A
    $relpath = substr($fullname, strlen($assistant->get_top_dir()));
    $fullurl = $starturl.'/XAZ';
//  $prefix = substr($fullurl, 0, (strlen($fullurl) - strlen($relpath) + 1)); //includes CMS_ROOT_URL
    $l1 = strlen($fullurl) -strlen($relpath) + 1;
    $l2 = strlen(CMS_ROOT_URL);
    $prefix = substr($fullurl, $l2, $l1-$l2);
} else {
    $prefix = '';
}

$filemanager = cms_utils::get_module('FileManager');

//
// get our file list
//
$files = $thumbs = [];
$filesizename = [' bytes', ' kb', ' Mb']; //c.f. FileManager equivalents
$dh = dir($startdir);
while( FALSE !== ($filename = $dh->read()) ) {
    if( $filename == '.' ) continue;
    if( $filename == '..' && !$cwd ) continue;
    $fullname = cms_join_path($startdir,$filename);
    if( is_dir($fullname) ) {
        $file = [
        'isdir' => true,
        'name' => $filename,
        'ext' => '',
        'fullpath' => $fullname,
        'fullurl' => $starturl.'/'.$filename
        ];
        $file['relurl'] = $file['fullurl'];
        $file['icon'] = $filemanager->GetFileIcon('-',TRUE);
        $file['isparent'] = ($filename == '..');
        $parms = ['subdir'=>$filename,'inst'=>$inst,'sig'=>$sig];
        if( $type ) { $parms['type'] = $type; } // pass-thru, not for directory-selection
        //to avoid lots of urlencode changes, we simply merge all the params
        $url = $this->create_url($id,'filepicker',$returnid,['_enc'=>base64_encode(json_encode($parms))]).'&showtemplate=false';
        $file['chdir_url'] = $url;
        $files[$filename] = $file;
    } elseif( $accept_file($profile2,$assistant,$startdir,$filename) ) {
        $file = [
        'isdir' => false,
        'name' => $filename,
        'fullpath' => $fullname,
        'fullurl' => $starturl.'/'.$filename
        ];
        $relpath = $assistant->to_relative($fullname);
        $file['relurl'] = strtr($relpath,'\\','/');
        $p = strrpos($filename,'.');
        $file['ext'] = ($p > 0) ? strtolower(substr($filename,$p+1)) : '';
        $file['icon'] = $filemanager->GetFileIcon('.'.$file['ext'],FALSE);
        $file['filetype'] = $this->_typehelper->get_file_type($fullname);
        $file['isparent'] = FALSE;
        $file['is_thumb'] = $this->_typehelper->is_thumb($fullname);
        $file['dimensions'] = '';
        $file['is_image'] = $this->_typehelper->is_image($fullname);
        if( $file['is_image'] && !$file['is_thumb'] ) {
            $file['thumbnail'] = $get_thumbnail_tag($filename,$startdir,$starturl);
            $thumbs[] = 'thumb_'.$filename;
            $imgsize = @getimagesize($fullname);
            if( $imgsize ) $file['dimensions'] = $imgsize[0].' x '.$imgsize[1];
        }
        $info = @stat($fullname);
        if( $info && $info['size'] > 0) {
            $i = floor(log($info['size'], 1024));
            $file['size'] = round(($info['size'] / 1024 ** $i), 2) . $filesizename[$i];
        } else {
            $file['size'] = '';
        }
        $files[$filename] = $file;
    }
}

if( $profile2->show_thumbs && $thumbs ) {
    // remove from the list thumbnails that are not orphaned
    foreach( $thumbs as $thumb ) {
        if( isset($files[$thumb]) ) unset($files[$thumb]);
    }
}
// done the loop, now sort
usort($files,$sortfiles);

$assistant2 = new PathAssistant($config,$config['root_path']);
$cwd_for_display = $assistant2->to_relative( $startdir );
$css_files = ['filepicker.css','filepicker.min.css'];
$mtime = -1;
$sel_file = '';
$bp = cms_join_path($this->GetModulePath(),'lib','css','');
foreach( $css_files as $file ) {
    $fp = $bp.$file;
    if( is_file($fp) ) {
        $fmt = filemtime($fp);
        if( $fmt > $mtime ) {
            $mtime = $fmt;
            $sel_file = '/lib/css/'.$file;
        }
    }
}

$smarty->assign('cssurl',(($sel_file) ? $this->GetModuleURLPath().$sel_file : ''));
$smarty->assign('cwd_for_display',$cwd_for_display);
$smarty->assign('cwd',$cwd);
$smarty->assign('files',$files);
$smarty->assign('sig',$sig);
$smarty->assign('inst',$inst);
$smarty->assign('mod',$this);
$smarty->assign('prefix',$prefix);
$smarty->assign('profile',$profile2);
$smarty->assign('type',$type);
$lang = [];
$lang['confirm_delete'] = $this->Lang('confirm_delete');
$lang['ok'] = $this->Lang('ok');
$lang['error_problem_upload'] = $this->Lang('error_problem_upload');
$lang['error_failed_ajax'] = $this->Lang('error_failed_ajax');
$smarty->assign('lang_js',json_encode($lang));
echo $this->ProcessTemplate('filepicker.tpl');

#
# EOF
#
