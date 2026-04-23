<?php
/*
CMSMS ModuleManger module script: upgrade
(C) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file ModuleManager.module.php applies to this file.
*/

if (!isset($gCms)) exit;
if (!($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules'))) exit;

if (version_compare($oldversion,'2.1.12') < 0) {
    // remove trailing '/' from recorded url
    $this->SetPreference('module_repository',ModuleManager::_dflt_request_url);
    // record setting if N/A
    $val = $this->GetPreference('disable_caching',-1);
    if ($val == -1) { $this->SetPreference('disable_caching',false); }
    // remove cached request-data
    $caches = glob(TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_*.dat');
    foreach ($caches as $fp) {
        if (is_file($fp)) {
            @unlink($fp);
        }
    }
    $caches = glob(PUBLIC_CACHE_LOCATION.DIRECTORY_SEPARATOR.'modmgr_*.dat');
    foreach ($caches as $fp) {
        if (is_file($fp)) {
            @unlink($fp);
        }
    }
}
