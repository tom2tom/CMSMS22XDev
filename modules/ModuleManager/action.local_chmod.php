<?php
/*
CMSMS ModuleManger module action: local_chmod
(C) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

$dir = cms_join_path(CMS_ROOT_PATH,'modules',$module);
$result = chmod_r($dir,0777);
if( !$result ) {
  $this->SetError($this->Lang('error_chmodfailed'));
}
else {
  audit('',$this->GetName(),'Changed permissions of '.$module.' directory');
  $this->SetMessage($this->Lang('msg_module_chmod'));
}

$this->RedirectToAdminTab();
?>
