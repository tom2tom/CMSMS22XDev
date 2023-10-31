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

//TODO use INI open_basedir setting where relevant e.g. to prevent zip-slip
//see https://www.php.net/manual/en/ini.core.php#ini.open-basedir

if (!function_exists("cmsms")) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

if (isset($params["cancel"])) {
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}
$selall = $params['selall'];
if( !is_array($selall) ) {
  $selall = unserialize($selall);
}
if (count($selall)==0) {
  $params["fmerror"]="nofilesselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}
if (count($selall)>1) {
  $params["fmerror"]="morethanonefiledirselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

$filename=$this->decodefilename($selall[0]);
$src= cms_join_path(CMS_ROOT_PATH,filemanager_utils::get_cwd(),$filename);
if( !file_exists($src) ) {
  $params["fmerror"]="filenotfound";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

$destdir = cms_join_path(CMS_ROOT_PATH,filemanager_utils::get_cwd());
$old = ini_get('open_basedir');

include_once(__DIR__.'/easyarchives/EasyArchive.class.php');
$archive = new EasyArchive();
ini_set('open_basedir',$destdir);
if( !endswith($destdir,DIRECTORY_SEPARATOR) ) $destdir.=DIRECTORY_SEPARATOR;

$res = $archive->extract($src,$destdir);
ini_set('open_basedir',$old);

$paramsnofiles["fmmessage"]="unpacksuccess"; //strips the file data
$this->Audit('',"File Manager","Unpacked file: ".$src);
$this->Redirect($id,"defaultadmin",$returnid,$paramsnofiles);

#
# EOF
#
?>
