<?php
#FileManager module suppprt script
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

if (!$this->CheckPermission('Modify Files')) exit;

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;uploadview.tpl",null,$modname,$smarty);

$tpl->assign('uformstart',$this->CreateFormStart($id,'upload',$returnid,'post','multipart/form-data'));
$tpl->assign('formend',$this->CreateFormEnd());
$tpl->assign('submit',$this->CreateInputSubmit($id,'submit',$this->Lang('submit')));
$tpl->assign('maxfilesize',$config['max_upload_size']);

$post_max_size = filemanager_utils::str_to_bytes(ini_get('post_max_size'));
$upload_max_filesize = filemanager_utils::str_to_bytes(ini_get('upload_max_filesize'));
$tpl->assign('max_chunksize',min($upload_max_filesize,$post_max_size-1024));
if (isset($_SERVER['HTTP_USER_AGENT'])) {
  if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== false ) {
    $tpl->assign('is_ie',1);
    $tpl->assign('ie_upload_message',$this->Lang('ie_upload_message'));
  }
}
$url = $this->create_url('m1_','upload',$returnid); //need to ensure admin $id?
$tpl->assign('action_url',str_replace('&amp;','&',$url));
$url = $this->create_url($id,'admin_fileview','',['noform' => 1]);
$tpl->assign('refresh_url',str_replace('&amp;','&',$url)); //? &showtemplate=false ?

$tpl->display();
