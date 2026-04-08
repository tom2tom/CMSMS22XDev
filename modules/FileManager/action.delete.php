<?php
#FileManager module action delete
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

use CMSMS\HookManager;

if( !function_exists('cmsms') ) exit;
if( !($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed()) ) exit;

if( isset($params['cancel']) ) {
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$selall = (!empty($params['selall'])) ? $params['selall'] : '';
if( $selall && !is_array($selall) ) {
  $tmp = @unserialize($selall, ['allowed_classes'=>[]]); // mask possible E_WARNING
  $selall = ($tmp !== false) ? $tmp : [$selall]; //might have been a (flat) single item
  $params['selall'] = $selall;
}
if ( !$selall ) {
  $params['fmerror'] = 'nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

// decode the sellall stuff
foreach( $selall as &$one ) {
  $one = $this->decodefilename($one);
}
unset($one);

// process form
$errors = array();
if( isset($params['submit']) ) {
  $curdir = CMS_ROOT_PATH.filemanager_utils::get_cwd(); // i.e. root.site-root-relative
  foreach( $selall as $item ) {
    // build complete path
    $fn = filemanager_utils::join_path($curdir,$item);
    if( !file_exists($fn) ) continue; // no error here.

    if( !is_writable($fn) ) {
      $errors[] = $this->Lang('error_notwritable',$item);
      continue;
    }

    $tn = '';
    if( is_dir($fn) ) {
      // check it's 'empty'
      $tmp = scandir($fn);
      if( count($tmp) > 2 ) { // allow for . and ..
        $errors[] = $this->Lang('error_dirnotempty',$item);
        continue;
      }
      @rmdir($fn);
      $type = 'directory';
    }
    else{
      if( filemanager_utils::is_image_file($fn) ) {
        // check for corresponding writable thumbnail
        $tn = filemanager_utils::join_path($curdir,'thumb_'.$item);
        if( file_exists($tn) ) {
          if( !is_writable($tn) ) {
            $errors[] = $this->Lang('error_thumbnotwritable',$item);
            continue;
          }
        }
        else {
          $tn = '';
        }
      }
      @unlink($fn);
      if( $tn ) @unlink($tn);
      $type = 'file';
    }

    audit('','FileManager',"Removed $type: $fn");
    $parms = array('file'=>$fn);
    if( $tn ) $parms['thumb'] = $tn;
    HookManager::do_hook('FileManager::OnFileDeleted', $parms); //aka send event
  } // foreach

  if( !$errors ) {
    $paramsnofiles['fmmessage'] = 'deletesuccess'; //strips the file data
    $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
  }
} // submit

// give everything to Smarty.
$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;delete.tpl",null,$modname,$smarty);

if( $errors ) {
  $this->ShowErrors($errors);
  $tpl->assign('errors',$errors);
}

$tpl->assign('selall',$selall); //un-munged data for UI display
$params['selall'] = (count($selall) > 1) ? serialize($params['selall']) : reset($params['selall']);
$tpl->assign('startform',$this->CreateFormStart($id,'fileaction',$returnid,'post','',false,'',$params));
$tpl->assign('endform',$this->CreateFormEnd());

$tpl->display();
