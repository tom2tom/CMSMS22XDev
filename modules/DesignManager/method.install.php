<?php
#-------------------------------------------------------------------------
# DesignManager module installation script
# (c) 2012 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
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
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#-------------------------------------------------------------------------

if( !isset($gCms) ) exit;

$this->SetPreference('lock_timeout',60);
$this->SetPreference('lock_refresh',120);

$fp = $config['uploads_path'].DIRECTORY_SEPARATOR.'designs';
$tp = $config['themes_path']; // OR $config['assets_path'].DIRECTORY_SEPARATOR.'designs';
if( is_dir($fp) ) {
    if( is_dir($tp) ) {
        $dirs = glob($fp.DIRECTORY_SEPARATOR.'*',GLOB_NOSORT|GLOB_ONLYDIR);
        foreach( $dirs as $tof ) {
            if( !$this->is_dir_unused($tof) ) {
                $tot = $tp.DIRECTORY_SEPARATOR.basename($tof);
                //TODO handle existing same-named item e.g. recursive_delete($tot) if any and is older
                rename($tof,$tot); //might fail and warn
            }
        }
        recursive_delete($fp);
    }
    else {
        rename($fp,$tp);
    }
}
elseif( !is_dir($tp) ) {
    mkdir($tp,0771,TRUE);
}
touch($tp.DIRECTORY_SEPARATOR.'index.html');
