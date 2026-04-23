<?php
/*
CMSMS ModuleManger module script: install
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if (!isset($gCms)) exit;
if (!($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules'))) exit;

$this->SetPreference('allowuninstall',0);
$this->SetPreference('disable_caching',0);
$this->SetPreference('dl_chunksize',256);
$this->SetPreference('latestdepends',1);
$this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
?>
