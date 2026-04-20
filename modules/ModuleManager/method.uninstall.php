<?php
/*
CMSMS ModuleManger module script: uninstall
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Modules')) exit;

$this->RemovePreference();
