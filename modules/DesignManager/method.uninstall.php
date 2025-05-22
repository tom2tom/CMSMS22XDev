<?php
#-------------------------------------------------------------------------
# DesignManager module uninstall script
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

//TODO any non-preserved db-changes
//TODO clear any $config['themes_path']/* which are not for actual themes
//OR any $config['assets_path'].DIRECTORY_SEPARATOR.'designs'/*;
//$fp = $config['themes_path'];
//if( $this->is_dir_empty($fp) ) {
//    recursive_delete($fp);
//}

$this->RemovePreference();
