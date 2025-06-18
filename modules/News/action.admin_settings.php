<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;admin_settings.tpl", null, $modname, $smarty);

require __DIR__.DIRECTORY_SEPARATOR.'function.admin_categoriestab.php';
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_customfieldstab.php';
require __DIR__.DIRECTORY_SEPARATOR.'function.admin_optionstab.php';

if( empty($seetab) ) {
    $seetab = (!empty($params['__activetab'])) ? $params['__activetab'] : '';
}
$tpl->assign('tab', $seetab);
$tpl->assign('endform', $this->CreateFormEnd());

$tpl->display();
