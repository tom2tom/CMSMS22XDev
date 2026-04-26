<?php
/*
CMSMS ModuleManger module action: local_upgrade
(C) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');

$modname = get_parameter_value($params,'mod');
if( !$modname ) {
  $this->SetError($this->Lang('error_missingparams'));
  $this->RedirectToAdminTab();
}

$ops = ModuleOperations::get_instance();
$result = $ops->UpgradeModule($modname);
if( !is_array($result) || !isset($result[0]) ) $result = array(FALSE,$this->Lang('error_moduleupgradefailed'));

if( $result[0] == FALSE ) {
  $this->SetError($result[1]);
  $this->RedirectToAdminTab();
}

$this->SetMessage($this->Lang('msg_module_upgraded',$modname));
$this->RedirectToAdminTab();

?>
