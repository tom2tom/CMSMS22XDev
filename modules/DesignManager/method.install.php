<?php
#-------------------------------------------------------------------------
# DesignManager module installation script
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
# You should have received a copy of the GNU General Public License
# along with this program; if not, read the license online at:
# https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;

$this->SetPreference('lock_timeout',60);
$this->SetPreference('lock_refresh',120);

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
                $original = basename($tof); //TODO might be case-inconsistent with installed design-name
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
}
touch($tp.DIRECTORY_SEPARATOR.'index.html');
