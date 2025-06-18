<?php
if(!isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News') ) return;

$modname = $this->GetName();
$tpl = $smarty->createTemplate("module_file_tpl:$modname;articlelist.tpl",null,$modname,$smarty);

require __DIR__.DIRECTORY_SEPARATOR.'function.admin_articlestab.php';

$tpl->display();
