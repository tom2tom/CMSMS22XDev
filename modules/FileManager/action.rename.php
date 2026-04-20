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
if (!($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed())) exit;

if (isset($params['cancel'])) {
  $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$selall = (!empty($params['selall'])) ? $params['selall'] : '';
if ($selall && !is_array($selall)) {
  $tmp = @unserialize($selall, ['allowed_classes'=>[]]); // mask possible E_WARNING
  $selall = ($tmp !== false) ? $tmp : [$selall]; //might have been a (flat) single item
  $params['selall'] = $selall;
}
if (!$selall) {
  $params['fmerror'] = 'nofilesselected';
  $this->Redirect($id, 'defaultadmin', $returnid, $params);
}
if (count($selall) > 1) {
  $params['fmerror'] = 'morethanonefiledirselected';
  $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$oldname = $this->decodefilename($selall[0]);
$newname = $oldname; //for initial input box

if (!empty($params['newname'])) {
  $barename = trim($params['newname']);
  $newname = strip_tags($barename);
  if (!filemanager_utils::is_valid_filename($newname)) {
    $this->ShowErrors($this->Lang('invaliddestname'));
  } else {
    $cwd = filemanager_utils::get_cwd();
    $fullnewname = filemanager_utils::join_path(filemanager_utils::get_full_cwd(), $barename);
    if (file_exists($fullnewname)) {
      $this->ShowErrors($this->Lang('namealreadyexists'));
    } else {
      $fulloldname = filemanager_utils::join_path(filemanager_utils::get_full_cwd(), $oldname);
      if (@rename($fulloldname, $fullnewname)) {
        $thumboldname = filemanager_utils::join_path(filemanager_utils::get_full_cwd(), 'thumb_'.$oldname);
        if (file_exists($thumboldname)) {
          $thumbnewname = filemanager_utils::join_path(filemanager_utils::get_full_cwd(), 'thumb_'.$barename);
          @rename($thumboldname, $thumbnewname);
        }
        $this->SetMessage($this->Lang('renamesuccess'));
        audit('', 'FileManager', 'Renamed file to '.$fullnewname);
        $this->Redirect($id, 'defaultadmin', $returnid, $paramsnofiles);
      } else {
        $this->SetError($this->Lang('renameerror'));
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
      }
    }
  }
}

$params['selall'] = $selall[0]; // flat value for next pass
//$params['fileaction'] = 'rename';

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;renamefile.tpl", null, $modname, $smarty);

$tpl->assign('startform', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params));
$tpl->assign('newnametext', $this->Lang('newname'));
$tpl->assign('newname', $newname);
$tpl->assign('newnameinput', $this->CreateInputText($id, 'newname', $newname, 40));
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->display();
