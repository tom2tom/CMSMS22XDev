<?php
#FileManager module action
#(c) 2006-8 Morten Poulsen <morten@poulsen.org>
#(c) 2008-2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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

if( isset($params['cancel']) ) {
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$selall = (!empty($params['selall'])) ? $params['selall'] : '';
if( $selall && !is_array($selall) ) {
  $tmp = @unserialize($selall, ['allowed_classes'=>[]]); // mask possible E_WARNING
  $selall = ($tmp !== false) ? $tmp : [$selall];
  $params['selall'] = $selall;
}
if( !$selall ) {
  $params['fmerror'] = 'nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

foreach( $selall as &$one ) {
  $one = $this->decodefilename($one);
}
unset($one);

$dirlist = filemanager_utils::get_dirlist();
if( !$dirlist ) {
  $params['fmerror'] = 'nodestinationdirs';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$cwd = filemanager_utils::get_cwd(); // site-root-relative
$advancedmode = filemanager_utils::check_advanced_mode();
$basedir = ($advancedmode) ? CMS_ROOT_PATH : $config['uploads_path'];

$errors = array();
if( isset($params['submit']) ) {
  $destdir = trim($params['destdir']); // $basedir-relative
  $destloc = filemanager_utils::join_path($basedir,$destdir);
  if( !is_dir($destloc) || !is_writable($destloc) ) $errors[] = $this->Lang('invalidmovedir');

  if( !$errors ) {
    $p1 = CMS_ROOT_PATH.$cwd;
    if( $p1 == $destloc && count($selall) > 1 ) $errors[] = $this->Lang('movedestdirsame');
  }

  if( !$errors ) {
    $destname = '';
    if( isset($params['destname']) && count($selall) == 1 ) {
      $destname = trim(strip_tags($params['destname']));
      if( $destname == '' ) $errors[] = $this->Lang('invaliddestname');
      //TODO block renaming of executable files unless authorised
    }

    if( !$errors ) {
      foreach( $selall as $file ) {
        $src = filemanager_utils::join_path(filemanager_utils::get_full_cwd(),$file);
        $dest = filemanager_utils::join_path($basedir,$destdir,$file);
        if( $destname ) $dest = filemanager_utils::join_path($basedir,$destdir,$destname);

        if( !file_exists($src) ) {
          $errors[] = $this->Lang('filenotfound')." $file";
          continue;
        }
        if( !is_readable($src) ) {
          $errors[] = $this->Lang('insufficientpermission',$file);
          continue;
        }
        if( file_exists($dest) ) {
          $errors[] = $this->Lang('fileexistsdest',basename($dest));
          continue;
        }

        $thumb = '';
        $src_thumb = '';
        $dest_thumb = '';
        if( filemanager_utils::is_image_file($file) ) {
          $tmp = 'thumb_'.$file;
          $src_thumb = filemanager_utils::join_path(CMS_ROOT_PATH,$cwd,$tmp);
          $dest_thumb = filemanager_utils::join_path($basedir,$destdir,$tmp);
          if( $destname ) $dest_thumb = filemanager_utils::join_path($basedir,$destdir,'thumb_'.$destname);

          if( file_exists($src_thumb) ) {
            $thumb = $tmp;
            // have a thumbnail
            if( !is_readable($src_thumb) ) {
              $errors[] = $this->Lang('insufficientpermission',$thumb);
              continue;
            }
            if( file_exists($dest_thumb) ) {
              $errors[] = $this->Lang('fileexistsdest',$thumb);
              continue;
            }
          }
        }

        // here we can move the file/dir
        $res = copy($src,$dest);
        if( !$res ) {
          $errors[] = $this->Lang('copyfailed',$file);
          continue;
        }
        if( $thumb ) {
          $res = copy($src_thumb,$dest_thumb);
          if( !$res ) {
            $errors[] = $this->Lang('copyfailed',$thumb);
            continue;
          }
        }
      } // foreach
    } // no error
  } // no error

  if( !$errors ) {
    $paramsnofiles['fmmessage'] = 'copysuccess'; //strips the file data
    $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
  }
} // submit

if( $errors ) $this->ShowErrors($errors);

// $dirlist options are effectivley $basedir-relative
// so likewise for the current/selected option
if( $advancedmode ) {
  $sel = $cwd;
}
else {
  $n = strlen($basedir) - strlen(CMS_ROOT_PATH);
  $sel = substr($cwd, $n);
}

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;copy.tpl",null,$modname,$smarty);

$params['selall'] = (count($selall) > 1) ? serialize($params['selall']) : reset($params['selall']);
$tpl->assign('startform',$this->CreateFormStart($id,'fileaction',$returnid,'post','',false,'',$params));
$tpl->assign('endform',$this->CreateFormEnd());
$tpl->assign('dirlist',$dirlist);
$tpl->assign('dirsel',$sel);
$tpl->assign('selall',$selall); //unmunged for UI display

$tpl->display();

?>
