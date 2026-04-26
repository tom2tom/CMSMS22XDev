<?php
/*
CMSMS ModuleManger module tab-populator: admin_installed
(C) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;

try {
    $allmoduleinfo = ModuleManager\ModuleInfo::get_all_module_info($connection_ok);
    uksort($allmoduleinfo,'strnatcasecmp');
    $tpl->assign('module_info',$allmoduleinfo);
}
catch( Exception $e ) {
    debug_to_log($e);
    $this->ShowErrors($e->GetMessage());
    return;
}
$tpl->assign('allow_export',(!empty($config['developer_mode']))?1:0);
$tpl->assign('allow_modman_uninstall',$this->GetPreference('allowuninstall',0));
?>
