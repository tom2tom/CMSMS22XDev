<?php
/*
CMSMS CmsJobManager module installation script
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

use CMSMS\Async\Job;

if( !isset($gCms) ) exit;

$this->CreateEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->AddEventHandler('Core','ModuleUninstalled',FALSE);

$taboptarray = ['mysqli' => 'ENGINE MyISAM'];
$dict = NewDataDictionary($db);

$tbl = CMS_DB_PREFIX.Job::RECORDTABLE;
$flds = '
id I UNSIGNED KEY,
name C(255) NOTNULL,
module C(160),
errors I1 UNSIGNED NOTNULL DEFAULT 0,
created I UNSIGNED NOTNULL,
start I UNSIGNED,
until I UNSIGNED,
priority I1 UNSIGNED NOTNULL DEFAULT 2,
recurs C(32),
data X
';
$sqlarray = $dict->CreateTableSQL($tbl,$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$sql = <<<EOS
ALTER TABLE `$tbl`
CHANGE `module` `module` varchar(160) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL
EOS;
$db->Execute($sql);

$db->CreateSequence("{$tbl}_seq");

$taboptarray = ['mysqli' => 'ENGINE MyISAM CHARACTER SET ascii'];
$tbl = CMS_DB_PREFIX.Job::CACHETABLE;
$flds = '
id I UNSIGNED NOTNULL,
propname C(255) NOTNULL,
value I UNSIGNED
';
$sqlarray = $dict->CreateTableSQL($tbl,$flds,$taboptarray);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_id',$tbl,'id');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->CreateIndexSQL('i_propname',$tbl,'propname');
$dict->ExecuteSQLArray($sqlarray);
/*
Preferences used for recording operational data (some are siteprefs)
 'last_async_trigger'
 'last_badjob_run'
 'last_processing'
 'tasks_lastcheck'
 'jobs_lock'
 'JobLastrun:Core:'.CronJob-name or 'JobLastrun:module-name:CronJob-name'
Extra siteprefs manageable by job-permitted users
 'jobs_interval'
 'jobs_timeout'
$config settings which might be provided to prevail over siteprefs
 'cmsjobmanager_asyncfreq' (formerly 'cmsjobmgr_asyncfreq')
 'cmsjobmanager_timelimit'
*/
