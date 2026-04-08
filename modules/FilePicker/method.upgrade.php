<?php
# BEGIN_LICENSE
# #-------------------------------------------------------------------------
# Module FilePicker upgrade script
# (c) 2026 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# You should have received a copy of the GNU General Public License
# along with this program. If not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------
# END_LICENSE

use FilePicker\ProfileDAO;

if( version_compare($oldversion, '1.0.9') < 0 ) {
    // consistent names for datestamp (I) fields
    $tbl = ProfileDAO::table_name();
    $db->Execute("ALTER TABLE `$tbl`
RENAME COLUMN `create_date` TO `created`,
RENAME COLUMN `modified_date` TO `modified`");
    // adjust property names and values in recorded serialized profiles
    $data = $db->GetArray("SELECT id,data,created,modified FROM $tbl");
    if( $data ) {
        $sql = "UPDATE $tbl SET data=? WHERE id=?";
        $from = [
        '/"id";.+?;/',
        '/s:11:"create_date";.+?;/',
        '/s:13:"modified_date";.+?;/'
        ];
        foreach( $data as $row ) {
            $to = [
            '"id";i:'.(int)$row['id'].';',
            's:7:"created";i:'.(int)$row['created'].';',
            's:8:"modified";i:'.(int)$row['modified'].';'
            ];
            $fix = preg_replace($from, $to, $row['data']);
            $db->Execute($sql, [$fix, (int)$row['id']]);
        }
    }
}
