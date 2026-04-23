<?php
# BEGIN_LICENSE
# #-------------------------------------------------------------------------
# Module FilePicker installation script
# (c) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# (c) 2016 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#-------------------------------------------------------------------------
# This file is part of FilePicker
# FilePicker is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FilePicker is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------
# END_LICENSE

use FilePicker\ProfileDAO;

if (!isset($gCms)) exit;
if (!($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules'))) exit;

$taboptarray = array('mysqli' => 'ENGINE=MyISAM','mysql' => 'ENGINE=MyISAM');
$dict = NewDataDictionary($db);

$tbl = ProfileDAO::table_name();
$flds = '
id I KEY AUTO,
name C(100) NOTNULL,
data X,
created I,
modified I';

try {
    $sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
    $dict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dict->CreateIndexSQL(CMS_DB_PREFIX.'cmsfp_idx0', $tbl, 'name', ['UNIQUE']);
    $dict->ExecuteSQLArray($sqlarray);

    $db->CreateSequence("{$tbl}_seq");
    $db->Execute("ALTER TABLE `{$tbl}_seq` MODIFY `id` int unsigned NOT NULL");
}
catch(Exception $e) {
    return $e->getMessage();
}
?>
