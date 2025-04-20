<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

$ops = ModuleOperations::get_instance();
$modinstance = $ops->get_module_instance($module,'',TRUE);
if( !is_object($modinstance) ) {
  $this->SetError($this->Lang('error_getmodule',$module));
  $this->RedirectToAdminTab();
}

$modname = $this->GetName();
$tpl = $smarty->CreateTemplate("module_file_tpl:$modname;local_about.tpl",null,$modname,$smarty);

$tpl->assign('back_url',$this->create_url($id,'defaultadmin',$returnid));
$tpl->assign('about_page',$modinstance->GetAbout());
$tpl->assign('about_title',$this->Lang('about_title',$modinstance->GetName()));

$tpl->display();
?>
