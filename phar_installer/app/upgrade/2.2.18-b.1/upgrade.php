<?php
$dbdict = NewDataDictionary($db);

status_msg('performing database changes for CMSMS 2.2.18');

$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'users','password C(128)');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'users','admin_access I1 DEFAULT 1');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'users','active I1 DEFAULT 1');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('updating database schema to 203');
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 203';
$db->Execute($query);
