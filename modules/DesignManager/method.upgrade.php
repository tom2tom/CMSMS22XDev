<?php
#-------------------------------------------------------------------------
# DesignManager module upgrade script
# (c) 2015 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/#LicenseURLs
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;
if( !($gCms->test_state(CmsApp::STATE_INSTALL) || $this->CheckPermission('Modify Modules')) ) exit;

if( version_compare($oldversion,'1.2') < 0 ) {
    // migrate stored files
    $tp = $config['themes_path']; // OR $config['assets_path'].DIRECTORY_SEPARATOR.'themes';
    if( !is_dir($tp) ) {
        mkdir($tp,0777,TRUE);
    }

    foreach( ['designs','themes'] as $bd ) {
        $fp = $config['uploads_path'].DIRECTORY_SEPARATOR.$bd;
        if( is_dir($fp) ) {
            $dirs = glob($fp.DIRECTORY_SEPARATOR.'*',GLOB_NOSORT|GLOB_ONLYDIR);
            foreach( $dirs as $tof ) {
                if( !$this->is_dir_unused($tof) ) {
//TODO use dm_utils::munge_name_to_dir() for corresponding design-name
                    $n = 0;
                    $original = basename($tof);
                    $tot = $tp.DIRECTORY_SEPARATOR.$original;
                    while ( file_exists($tot) ) {
                        ++$n;
                        $tot = $tp.DIRECTORY_SEPARATOR."$original($n)";
                    }
                    rename($tof,$tot);
                }
            }
            recursive_delete($fp);
        }
        touch($tp.DIRECTORY_SEPARATOR.'index.html');
    }
    touch($tp.DIRECTORY_SEPARATOR.'index.html');
}

$pref = CMS_DB_PREFIX;

if( version_compare($oldversion,'1.2.1') < 0 ) {
    // remove (all users') redundant filter-preferences
    $modname = $this->GetName();
    $sql = "DELETE FROM {$pref}userprefs WHERE preference LIKE '$modname%'";
    $db->Execute($sql);
}

if( version_compare($oldversion,'1.3') < 0 ) {
    $dbr = $db->GetOne("SELECT version FROM {$pref}layout_designs LIMIT 1");
    if( $db->ErrorNo() != 0 ) {
        $db->Execute("ALTER TABLE `{$pref}layout_designs`
ADD COLUMN `version` varchar(20) default '1.0' AFTER `description`,
ADD COLUMN `requires` varchar(320) AFTER `version`");
    }
}
