<?php
/*
CMSMS CmsJobManager module uninstallation script
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

use CMSMS\Async\Job;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$dict = NewDataDictionary($db);
$tbl = CMS_DB_PREFIX.Job::RECORDTABLE;
$sqlarray = $dict->DropTableSQL($tbl);
$dict->ExecuteSQLArray($sqlarray);
$db->DropSequence("{$tbl}_seq");
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Job::CACHETABLE);
$dict->ExecuteSQLArray($sqlarray);

cms_siteprefs::remove('JobLastrun:',true);
$this->RemovePreference();
//$this->RemovePermission(CmsJobManager::MANAGE_JOBS);
$this->RemoveEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->RemoveEventHandler('Core','ModuleUninstalled');
