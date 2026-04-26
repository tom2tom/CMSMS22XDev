<?php
/*
CMSMS ModuleManger module action: local_install
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
$result = $ops->InstallModule($modname);
if( !is_array($result) || !isset($result[0]) ) $result = array(FALSE,$this->Lang('error_moduleinstallfailed'));

if( $result[0] == FALSE ) {
  $this->SetError($result[1]);
  $this->RedirectToAdminTab();
}

$modinstance = $ops->get_module_instance($modname,'',TRUE);
if( !is_object($modinstance) ) {
  // uh-oh...
  $this->SetError($this->Lang('error_getmodule',$modname));
  $this->RedirectToAdminTab();
}

$msg = $modinstance->InstallPostMessage();
if( !$msg ) $msg = $this->Lang('msg_module_installed',$modname);
$this->SetMessage($msg);
$this->RedirectToAdminTab();

?>
