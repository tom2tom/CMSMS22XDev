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
if (!$this->CheckPermission('Modify Site Preferences')) return;

$advancedmode=$this->GetPreference('advancedmode',0);
$showhiddenfiles=$this->GetPreference('showhiddenfiles',0);
$showthumbnails=$this->GetPreference('showthumbnails',1);
$createthumbnails=$this->GetPreference('create_thumbnails',1);
$iconsize=$this->GetPreference('iconsize','16px');
$permissionstyle=$this->GetPreference('permissionstyle','xxx');

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;settings.tpl",null,$modname,$smarty);

//$tpl->assign('path',$this->CreateInputHidden($id,'path',$path)); //why?
$tpl->assign('advancedmode',$advancedmode);
$tpl->assign('showhiddenfiles',$showhiddenfiles);
$tpl->assign('showthumbnails',$showthumbnails);
$tpl->assign('create_thumbnails',$createthumbnails);
$iconsizes = array(
 '16px' => $this->Lang('smallicons').' (16px)',
 '32px' => $this->Lang('largeicons').' (32px)');
$tpl->assign('iconsizes',$iconsizes);
$tpl->assign('iconsize',$iconsize);
$permstyles=array(
 'xxxxxxxxx'=>$this->Lang('rwxstyle'),
 'xxx'=>$this->Lang('755style'));
$tpl->assign('permstyles',$permstyles);
$tpl->assign('permissionstyle',$permissionstyle);

$tpl->display();

?>
