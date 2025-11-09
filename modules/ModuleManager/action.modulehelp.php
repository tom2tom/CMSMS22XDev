<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module ModuleManager action
# (c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if (!isset($gCms)) exit;

$this->SetCurrentTab('modules');

$name = get_parameter_value($params,'name');
if( !$name ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
//return;
}

$version = get_parameter_value($params,'version');
if( !$version ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
//return;
}

$url = $this->GetPreference('module_repository');
if( !$url ) {
  $this->SetError($this->Lang('error_norepositoryurl'));
  $this->RedirectToAdminTab();
//return;
}
$url .= '/modulehelp';

$xmlfile = get_parameter_value($params,'filename');
if( !$xmlfile ) {
  $this->SetError($this->Lang('error_nofilename'));
  $this->RedirectToAdminTab();
//return;
}


$req = new modmgr_cached_request();
$req->execute($url,array('name'=>$xmlfile));
$status = $req->getStatus();
$result = $req->getResult();
if( $status != 200 || $result == '' ) { // some 300's ok?
  $this->SetError($this->Lang('error_request_problem'));
  $this->RedirectToAdminTab();
//return;
}
$help = json_decode($result,true);
if( !$help ) {
  $this->SetError($this->Lang('error_nodata'));
  $this->RedirectToAdminTab();
//return;
}

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;remotecontent.tpl",null,$modname,$smarty);
$tpl->assign('title',$this->Lang('helptxt'));
$tpl->assign('modulename',$name);
$tpl->assign('moduleversion',$version);
$tpl->assign('xmlfile',$xmlfile);
$tpl->assign('content',$help);
$tpl->assign('back_url',$this->create_url($id,'defaultadmin',$returnid));
$tpl->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid, $this->Lang('back_to_module_manager')));
$tpl->display();

#
# EOF
#
?>
