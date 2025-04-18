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

//NOTE this action was actually used only in CMSMS 1.2 to 1.10 - TODO omit from 2.2+

if (!function_exists("cmsms")) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

if(!isset($params["filename"]) || !isset($params["path"])) {
	$this->Redirect($id, 'defaultadmin');
}

if( !filemanager_utils::test_valid_path($params['path']) ) {
	$this->Redirect($id, 'defaultadmin',$returnid,array("fmerror"=>"fileoutsideuploads"));
}

$config = $gCms->GetConfig();
//$fullname=$this->Slash($params["path"],$params["filename"]); repeated below
$fullname=$this->Slash($config["root_path"],$fullname);

if (isset($params["newmode"])) {
	if (isset($params["cancel"])) {
		$this->Redirect($id,"defaultadmin",$returnid,array("path"=>$params["path"],"fmmessage"=>"chmodcancelled"));
	} else {
		if ($this->SetModeWin($params["newmode"],$fullname)) {
			$this->Redirect($id,"defaultadmin",$returnid,array("path"=>$params["path"],"fmmessage"=>"chmodsuccess"));
		} else {
			$this->Redirect($id,"defaultadmin",$returnid,array("path"=>$params["path"],"fmerror"=>"chmodfailure"));
		}
	}
} else {
	$currentmode=$this->GetModeWin($params["path"],$params["filename"]);
	$tpl = $smarty->CreateTemplate($this->GetTemplateResource('chmodfilewin.tpl'), null, null, $smarty);

	$tpl->assign('startform', $this->CreateFormStart($id, 'chmodfilewin', $returnid));
	$tpl->assign('filename', $this->CreateInputHidden($id,"filename",$params["filename"]));
	$tpl->assign('path', $this->CreateInputHidden($id,"path",$params["path"]));
	$tpl->assign('endform', $this->CreateFormEnd());
	$tpl->assign('newmodetext', $this->Lang("newpermissions"));
	$tpl->assign('modeswitch',
		$this->CreateInputRadioGroup($id, "newmode", array($this->Lang("writable")=>"777", $this->Lang("writeprotected")=>"444"), $currentmode));
	$tpl->assign('modeswitchof', $this->GetModeTable($id,$this->GetPermissions($params["path"],$params["filename"])));
	$tpl->assign('submit', $this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')));
	$tpl->assign('cancel', $this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));

	$tpl->display();
}

?>
