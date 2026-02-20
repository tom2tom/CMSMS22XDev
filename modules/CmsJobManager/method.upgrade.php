<?php
/*
CMSMS CmsJobManager module upgrade script
(C) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
The license at the top of file CmsJobManager.module.php applies to this file.
*/

use CMSMS\Async\Job;

if (!isset($gCms)) exit;

if( version_compare($oldversion,'0.2.0') < 0 ) {
    //alter table-fields
    $tbl = CMS_DB_PREFIX.Job::RECORDTABLE;
    $db->Execute("TRUNCATE TABLE `$tbl`");
    $sql = <<<EOS
ALTER TABLE `$tbl`
CHANGE `id` `id` int unsigned NOT NULL,
CHANGE `module` `module` varchar(160) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
CHANGE `errors` `errors` tinyint  UNSIGNED NOT NULL DEFAULT 0,
CHANGE `created` `created` int UNSIGNED NOT NULL,
CHANGE `start` `start` int UNSIGNED DEFAULT NULL,
CHANGE `recurs` `recurs` varchar(32) DEFAULT NULL AFTER until,
CHANGE `until` `until` int UNSIGNED,
CHANGE `data` `data` text DEFAULT NULL,
ADD `priority` tinyint UNSIGNED NOT NULL DEFAULT 2 AFTER until
EOS;
    $db->Execute($sql);

    //add sequence table for job id's
    $db->CreateSequence("{$tbl}_seq");

    //add table for persistent-caching of timestamps
    $taboptarray = ['mysqli' => 'ENGINE MyISAM CHARACTER SET ascii','mysql' => 'ENGINE MyISAM CHARACTER SET ascii'];
    $dict = NewDataDictionary($db);
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
    // remove superseded permission (migrated to Core)
    $tbl = CMS_DB_PREFIX.'permissions';
    $data = $db->GetAssoc("SELECT permission_source,permission_id FROM $tbl WHERE permission_name='Manage Jobs'");
    if( $data && isset($data['CmsJobManager']) ) {
        $O = (int)$data['CmsJobManager'];
        $tbl = CMS_DB_PREFIX.'group_perms';
        if( isset($data['Core']) ) {
            $N = (int)$data['Core'];
            $db->Execute("UPDATE $tbl SET permission_id=$N WHERE permission_id=$O");
        }
        else {
            $db->Execute("DELETE FROM $tbl WHERE permission_id=$O");
        } 
    }
    $this->RemovePermission('Manage Jobs'); // former CmsJobManager::MANAGE_JOBS
    // remove any sitepref used for redundant timestamps or lock
    $this->RemovePreference();
    foreach ([
     'ClearCache_lastexecute',
     'CmsSecurityCheckTask',
     'CmsVersionCheckTask',
     'PruneAdminlog_lastexecute',
     'ReduceAdminlog_lastexecute'
    ] as $key) {
        cms_siteprefs::remove($key);
    }
}
