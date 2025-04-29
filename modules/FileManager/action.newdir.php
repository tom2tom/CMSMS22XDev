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
if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid, $params);

$path = filemanager_utils::get_cwd();

$newdirname = '';
if (isset($params['newdirname'])) {
  $newdirname = trim($params['newdirname']);

  if (!filemanager_utils::is_valid_filename($newdirname) ) {
    echo $this->ShowErrors($this->Lang('invalidnewdir'));
    //fallthrough
  } else {
    $base = CMS_ROOT_PATH;
    $newdir = $this->Slash($params['path'], $newdirname);
    $newdir = $this->Slash($base, $newdir);

    if (is_dir($newdir)) {
      echo $this->ShowErrors($this->Lang('direxists'));
      //fallthrough
    } else {
      if (mkdir($newdir)) {
        $params['fmmessage'] = 'newdirsuccess'; //strips the file data
        audit('', 'FileManager', 'Created new directory ' . $newdirname);
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
      } else {
        $params['fmerror'] = 'newdirfail';
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
      }
    }
  }
}

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;newdir.tpl",null,$modname,$smarty);

$tpl->assign('startform', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params));
$tpl->assign('newdirtext', $this->Lang('newdir'));
$tpl->assign('newdirname',$newdirname);
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->display();

?>
