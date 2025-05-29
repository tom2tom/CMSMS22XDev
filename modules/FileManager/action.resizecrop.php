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

//TODO current js backend (jrac) is elderly, poorly-maintained if at all, and GPL2 only
//consider replacement e.g. from https://github.com/Traackr/resizeAndCrop

if( !function_exists('cmsms') ) exit;
if( !($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed()) ) exit;

if( isset($params['cancel']) ) {
//unset($params['selall']);
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$selall = (!empty($params['selall'])) ? $params['selall'] : '';
if( $selall && !is_array($selall) ) {
  $tmp = @unserialize($selall, ['allowed_classes'=>[]]); // mask possible E_WARNING
  $selall = ($tmp !== false) ? $tmp : [$selall];
  $params['selall'] = $selall;
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
if( !file_exists($src) ) {
  $params['fmerror']='filenotfound';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
$imageinfo = getimagesize($src);
if( !$imageinfo || !isset($imageinfo['mime']) || !startswith($imageinfo['mime'],'image') ) {
  $this->SetError($this->Lang('filenotimage'));
  $this->Redirect($id,'defaultadmin',$returnid);
}
if( !is_writable($src) ) {
  $this->SetError($this->Lang('filenotimage'));
  $this->Redirect($id,'defaultadmin',$returnid);
}

//
// handle submit action(s).
//

//Get GDImage
$instance = imageEditor::open($src);
if( !is_object($instance) ) {
  if( $instance ) {
    $msg = $instance; //error message TODO use error key, to support translation
  }
  elseif( $instance === FALSE ) {
     $msg = $this->Lang('error_open');
  }
  else {
    $msg = $this->Lang('fileimagetype');
  }
  $this->SetError($msg);
  $this->Redirect($id,'defaultadmin',$returnid);
}

if(empty($params['reset'])
   && !empty($params['cx']) && !empty($params['cy'])
   && !empty($params['cw']) && !empty($params['ch'])
   && !empty($params['iw']) && !empty($params['ih'])) {

  //Get the mimetype
  $mimeType = imageEditor::getMime($src); // c.f. $imageinfo['mime']

  //Resize it if necessary
  if( !empty($params['iw']) && !empty($params['ih']) ) {
      $instance = imageEditor::resize($instance, $mimeType, $params['iw'], $params['ih']);
  }

  //Crop it if necessary
  if( !empty($params['cx']) && !empty($params['cy']) && !empty($params['cw']) && !empty($params['ch']) ) {
      $instance = imageEditor::crop($instance, $mimeType, $params['cx'], $params['cy'], $params['cw'], $params['ch']);
  }

  //Save it
  $res = imageEditor::save($instance, $src, $mimeType);
  if( $this->GetPreference('create_thumbnails') ) filemanager_utils::create_thumbnail($src, NULL, TRUE);

  $this->Redirect($id,'defaultadmin',$returnid);
}

//
// build the form
//
$params['selall'] = $selall[0]; // flat value for next pass

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;pie.tpl",null,$modname,$smarty);

$tpl->assign('formstart',$this->CreateFormStart($id,'resizecrop',$returnid,'post','',false,'',$params));
$tpl->assign('formend',$this->CreateFormEnd());
$tpl->assign('filename',$filename);
$url = filemanager_utils::get_cwd_url()."/$filename";
$tpl->assign('image',$url);
$tpl->assign('image_width',$imageinfo[0]);

$tpl->display();

?>
