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

// TODO handle $params['newsort'] in redirect
// TODO handle path & url separators robustly

if (!function_exists('cmsms')) exit;
if (!($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed())) exit;
if (!isset($params['path'])) $this->Redirect($id, 'defaultadmin');
if( !filemanager_utils::test_valid_path($params['path']) ) {
  $this->Redirect($id, 'defaultadmin',$returnid,array('fmerror'=>'fileoutsideuploads'));
}

$path = $params['path'];

$fileaction = '';
if (isset($params['fileaction'])) $fileaction = $params['fileaction'];

$selfiles = array();
$seldirs = array();
$paramsnofiles = array();
//$somethingselected = false;
foreach ($params as $key=>$value) {
  if (strncmp($key,'file_',5) == 0) {
    $selfiles[] = $this->decodefilename(substr($key,5));
  } elseif (strncmp($key,'dir_',4) == 0) {
    $seldirs[] = $this->decodefilename(substr($key,4));
  } else {
    $paramsnofiles[$key] = $value;
  }
}

$selall = array_merge($seldirs,$selfiles);

// get the dirs in the uploads tree
$dirlist = array();
//$dirlist[$this->Lang('selecttargetdir')] = '-';
$filerec = get_recursive_file_list($config['uploads_path'], array(), -1, 'DIRS');
foreach ($filerec as $key => $value) {
  $value1 = str_replace(CMS_ROOT_PATH, '', $value);
  //ignore current dir
  if ($value1 == ($path . DIRECTORY_SEPARATOR)) {
    continue;
  }
  //ignore if hidden dir in the path TODO unless settings allow hiddens ?
  $dirs = explode(DIRECTORY_SEPARATOR, $value1);
  foreach ($dirs as $dir) {
    if ($dir && $dir[0] == '.') { //TODO use OS-tailored hidden-items method
      continue 2;
    }
  }
  $value2 = $this->Slashes($value1);
  $dirlist[$value2] = $value2;
}

if (isset($params['fileactionnewdir']) || $fileaction == 'newdir') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.newdir.php';
  return;
}

if (isset($params['fileactionrename']) || $fileaction == 'rename') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.rename.php';
  return;
}

if (isset($params['fileactiondelete']) || $fileaction == 'delete') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.delete.php';
  return;
}

if (isset($params['fileactioncopy']) || $fileaction == 'copy') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.copy.php';
  return;
}

if (isset($params['fileactionmove']) || $fileaction == 'move') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.move.php';
  return;
}

if (isset($params['fileactionunpack']) || $fileaction == 'unpack') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.unpack.php';
  return;
}

if (isset($params['fileactionthumb']) || $fileaction == 'thumb') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.thumb.php';
  return;
}

if (isset($params['fileactionresizecrop']) || $fileaction == 'resizecrop') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.resizecrop.php';
  return;
}

if (isset($params['fileactionrotate']) || $fileaction == 'rotate') {
  require __DIR__.DIRECTORY_SEPARATOR.'action.rotate.php';
  return;
}

$this->Redirect($id,'defaultadmin',$returnid,array('path'=>$params['path'],'fmerror'=>'unknownfileaction'));
