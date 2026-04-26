<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module ModuleManager action: moduleabout
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
#END_LICENSE

if (!isset($gCms)) exit;

$this->SetCurrentTab('modules');

$name = get_parameter_value($params,'name');
if( !$name ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
}

$version = get_parameter_value($params,'version');
if( !$version ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
}

$url = $this->GetPreference('module_repository');
if( !$url ) {
  $this->SetError($this->Lang('error_norepositoryurl'));
  $this->RedirectToAdminTab();
}
$url .= '/moduleabout';

$xmlfile = get_parameter_value($params,'filename');
if( !$xmlfile ) {
  $this->SetError($this->Lang('error_nofilename'));
  $this->RedirectToAdminTab();
}

$req = new ModuleManager\cached_http_request();
$req->send($url,array('name'=>$xmlfile));
$status = $req->getStatus();
if( $status != 200 ) { // some 300's ok?
  $this->SetError($this->Lang('error_request_problem'));
  $this->RedirectToAdminTab();
}
$result = $req->getResult();
$about = ($result) ? json_decode($result,true) : '';
if( !$about ) {
  $about = $this->Lang('msg_noinfo');
}
$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;remotecontent.tpl",null,$modname,$smarty);
$tpl->assign('title',$this->Lang('abouttxt'));
$tpl->assign('modulename',$name);
$tpl->assign('moduleversion',$version);
$tpl->assign('xmlfile',$xmlfile);
$tpl->assign('content',$about);
$tpl->assign('back_url',$this->create_url($id,'defaultadmin',$returnid));
$tpl->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid,$this->Lang('back_to_module_manager')));
$tpl->display();
?>
