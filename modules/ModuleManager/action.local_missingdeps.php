<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');
$info = ModuleManagerModuleInfo::get_module_info($module);

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;local_missingdeps.tpl",null,$modname,$smarty);

$tpl->assign('back_url',$this->create_url($id,'defaultadmin',$returnid));
$tpl->assign('info',$info);

$tpl->display();
#
# EOF
#
?>
