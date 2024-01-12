<?php
#-------------------------------------------------------------------------
# Module: AdminSearch - A CMSMS addon module to provide admin side search capbilities.
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
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------

$this->CreatePermission('Use Admin Search',lang('perm_Use_Admin_Search'));//TODO migrate to module-lang

$groupops = GroupOperations::get_instance();
$groups = $groupops->LoadGroups();

if( is_array($groups) && count($groups) ) {
  foreach( $groups as $one_group ) {
    $one_group->GrantPermission('Use Admin Search');
  }
}
#
# EOF
#
?>
