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

if (!function_exists('cmsms')) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;

if (isset($params['cancel'])) {
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
$selall = $params['selall'];
if (!is_array($selall) ) {
  $selall = unserialize($selall);
}
if (!$selall) {
  $params['fmerror'] = 'nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
if (count($selall) > 1) {
  $params['fmerror'] = 'morethanonefiledirselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$filename = $this->decodefilename($selall[0]);
$src = filemanager_utils::join_path(CMS_ROOT_PATH,filemanager_utils::get_cwd(),$filename);
if (!file_exists($src)) {
  $params['fmerror'] = 'filenotfound';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$paramsnofiles = [];
$ext = strtolower(pathinfo($filename,PATHINFO_EXTENSION));

require_once cms_join_path(__DIR__,'easyarchives','EasyArchive.class.php');
$worker = new EasyArchive();

if (!isset($worker->WathArchive['.'.$ext])) {
  $paramsnofiles['fmerror'] = 'packfileopenfail'; //TODO expects filename
  $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
}
try {
  $res = $worker->extract($src,dirname($src).DIRECTORY_SEPARATOR);
  if ($res) {
    $paramsnofiles['fmmessage'] = 'unpacksuccess';
    audit('','File Manager','Unpacked file: '.$src);
  }
  else {
    $paramsnofiles['fmerror'] = 'unpackfail'; //TODO some detail
  }
} catch (Exception $e) {
  $paramsnofiles['fmerror'] = 'unpackfail'; //TODO $res = $e->getMessage();
}

$this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);

#
# EOF
#
?>
